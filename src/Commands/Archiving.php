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

    protected string $currentModel = 'global';
    protected string $logDisk = 'archive_logs';
    protected string $today;

    public function handle()
    {
        $this->logDisk = config('laravel_additions.log_disk');
        $this->today = now()->format('Y-m-d');
        $this->log(null);

        ini_set('max_execution_time', 60*60);
        ini_set('memory_limit','2G');

        $this->log('Start archiving models');


        try{

            $models = config('laravel_additions.models');
            /**
             * @var Model $model
             * @var array $options
             */
            foreach ($models as $model => $options){
                $this->currentModel = 'global';
                $this->log('Archiving '.$model);
                $this->currentModel = basename(str_replace('\\', '/', $model));
                $this->log(null);

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

                $this->log('Translations: ' .  ($hasTranslations ? 'yes' : 'no'));
                $chunkSize = $options['chunk_size'] ?? 1000;
                $this->log('Chunk size: ' .  $chunkSize);
                $query->chunk($chunkSize, function(Collection $results) use ($model, $options, $hasTranslations) {


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

                        $this->log(sprintf('Archiving %s translation rows', count($translationInserts)));
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
                        $this->log('Translation rows are inserted');

                    }

                    $this->log(sprintf('Archiving %s rows', count($inserts)));
                    $options['archive_model']::query()->insertOrIgnore($inserts);

                    $identifiers = array_column($inserts, $options['identifier']);
                    $identifiersCount = count($identifiers);
                    $inDbCount = $options['archive_model']::query()->whereIn($options['identifier'], $identifiers)->count();
                    if($identifiersCount !== $inDbCount){
                        throw new NotAllToArchiveException($model, $inDbCount, $identifiersCount);
                    }
                    $this->log('Rows are inserted');

                    $this->log('Remove old data');

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
                    $this->log('Old data removed');

                });

            }

            $this->currentModel = 'global';
            $this->log('Finished');

        }catch (\Exception $e){
            $this->log('Error: '.$e->getMessage());
            throw $e;
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

    private function log(?string $msg = null)
    {
        if(null === $msg){
            $msg = '';
        }else{
            $msg = sprintf('[%s] %s', now()->format('Y-m-d H:i:s.u'), $msg);
        }
        $this->info($msg);

        \Storage::disk($this->logDisk)->append($this->currentModel.'/'.$this->today.'.txt', $msg);
    }
}
