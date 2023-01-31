<?php

namespace Kroesen\LaravelAdditions\Models\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;

class HasOneThroughByMultipleFields extends HasManyThroughByMultipleFields
{
    use SupportsDefaultModels;

    public function getResults()
    {
        return $this->first() ?: $this->getDefaultFor($this->farParent);
    }

    public function match(array $models, Collection $results, $relation): array
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {

            $key = implode('_', array_map(function($local) use ($model) {
                return $this->getDictionaryKey($model->getAttribute($local));
            }, array_keys($this->firstKeys)));

            if (isset($dictionary[$key])) {
                $value = $dictionary[$key];
                $model->setRelation(
                    $relation, reset($value)
                );
            }
        }

        return $models;
    }
    protected function buildDictionary(Collection $results): array
    {
        $dictionary = [];

        // First we will create a dictionary of models keyed by the foreign key of the
        // relationship as this will allow us to quickly access all of the related
        // models without having to do nested looping which will be quite slow.
        foreach ($results as $result) {
            $dictionary[$result->laravel_through_key][] = $result;
        }

        return $dictionary;
    }

    public function newRelatedInstanceFor(Model $parent): Model
    {
        return $this->related->newInstance();
    }
}
