<?php

namespace Kroesen\LaravelAdditions\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Kroesen\LaravelAdditions\Exceptions\NotAllToArchiveException;
use Kroesen\LaravelAdditions\Helpers\Condition;

class Archiving extends Command
{

    protected $signature = 'kroesen:archive:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive models';

    public function handle()
    {
        $this->info('Get models');


        $models = config('laravel_additions.models');
        /**
         * @var Model $model
         * @var array $options
         */
        foreach ($models as $model => $options){

            $object = new $model;

            if(!isset($options['condition'])){
                $options['condition'] = [Condition::class, 'byDate'];
            }
            if(!isset($options['identifier'])){
                $options['identifier'] = 'id';
            }

            /** @var Builder $query */
            $query = $model::query();
            $options['condition']($query, $options['condition_options']);

            $hasTranslations = defined($model.'::TRANSLATION_CLASS')
                && isset($object->translationMapping)
                && defined($options['archive_model'].'::TRANSLATION_CLASS')
                && method_exists($model, 'translations')
                && $object->translations() instanceof Relation
            ;
            if ($hasTranslations) {
                $query->with('translations');
                $options['translations'] = [
                    'class' => $model::TRANSLATION_CLASS,
                    'archive_class' => $options['archive_model']::TRANSLATION_CLASS,
                    'mapping' => $object->translationMapping,
                ];
            }

            if(isset($options['order_by']) && !empty($options['order_by'])){
                foreach ($options['order_by'] as $orderBy){
                    if(is_string($orderBy)){
                        $query->orderBy($orderBy);
                        continue;
                    }
                    $query->orderBy($orderBy[0], $orderBy[1] ?? 'asc');
                }
            }

            $query->chunk($options['chunk_size'] ?? 1000, function(Collection $results) use ($model, $options, $hasTranslations) {


                $inserts = [];

                if(!$hasTranslations){
                    $inserts = $results->map(fn ($result) => $result->getRawOriginal())->toArray();
                }else{
                    $translationOptions = $options['translations'];
                    $mapping = $translationOptions['mapping'];
                    $foreignKey = $mapping['foreign_key'];
                    foreach ($results as $result){
                        $data = $result->getRawOriginal();
                        unset($data['translation']);
                        $inserts[] = $data;
                    }
                    $translationInserts = $results->pluck('translations')->flatten()->map(fn ($result) => $result->getRawOriginal())->toArray();

                    ($translationOptions['archive_class'])::query()->insertOrIgnore($translationInserts);



                    $check = [];
                    foreach ($translationInserts as $insert){
                        $lang = $insert['lang'];
                        if(!isset($check[$lang])){
                            $check[$lang] = [];
                        }
                        $check[$lang][] = $insert[$foreignKey];
                    }
                    foreach ($check as $lang => $translationIdentifiers){
                        $identifiersCount = count($translationIdentifiers);
                        $inDbCount = ($translationOptions['archive_class'])::query()
                            ->where('lang', $lang)
                            ->whereIn($foreignKey, $translationIdentifiers)
                            ->count()
                        ;
                        if($identifiersCount !== $inDbCount) {
                            throw new NotAllToArchiveException($translationOptions['class']." ($lang)", $inDbCount, $identifiersCount);
                        }
                    }

                }

                $options['archive_model']::query()->insertOrIgnore($inserts);

                $identifiers = array_column($inserts, $options['identifier']);

                $identifiersCount = count($identifiers);
                $inDbCount = $options['archive_model']::query()->whereIn($options['identifier'], $identifiers)->count();
                if($identifiersCount !== $inDbCount){
                    throw new NotAllToArchiveException($model, $inDbCount, $identifiersCount);
                }


                if($hasTranslations){
                    // Remove translations
                    try{
                        $model::query()->whereIn($options['identifier'], $identifiers)->delete();
                        $this->deleteTranslations($check, $foreignKey, $translationOptions);
                    }catch (\Exception){
                        $this->deleteTranslations($check, $foreignKey, $translationOptions);
                        $model::query()->whereIn($options['identifier'], $identifiers)->delete();
                    }
                }else{
                    $model::query()->whereIn($options['identifier'], $identifiers)->delete();
                }

            });

        }



    }

    private function deleteTranslations(array $check, string $foreignKey, array $translationOptions)
    {
        foreach ($check as $lang => $translationIdentifiers){
            ($translationOptions['class'])::query()
                ->where('lang', $lang)
                ->whereIn($foreignKey, $translationIdentifiers)
                ->delete()
            ;
        }
    }
}
