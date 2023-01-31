<?php

namespace Kroesen\LaravelAdditions\Models\Relations;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne as BaseHasOne;

class HasOneByMultipleFields extends BaseHasOne
{
    private ?Closure $callback;
    private array $keys;

    public function __construct(Builder $query, Model $parent, array $keys, string $foreignTable, ?Closure $callback = null)
    {

        $localKey = array_key_first($keys);
        $foreignKey = $keys[$localKey];
        parent::__construct($query, $parent, $foreignTable.'.'.$foreignKey, $localKey);
        $this->callback = $callback;
        $this->keys = $keys;
    }

    public function addEagerConstraints(array $models)
    {
        $query = $this->getRelationQuery();
        foreach($this->keys as $localKey => $foreignKey){
            $whereIn = $this->whereInMethod($this->parent, $localKey);

            $query->{$whereIn}(
                $foreignKey, $this->getKeys($models, $localKey)
            );
        }

        if($this->callback instanceof Closure){
            ($this->callback)($query);
        }
    }


    public function getResults()
    {
        if($this->callback instanceof Closure){
            ($this->callback)($this->query);
        }
        return parent::getResults();
    }


    protected function matchOneOrMany(array $models, Collection $results, $relation, $type): array
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {

            $key = implode('_', array_map(function($local) use ($model) {
                return $this->getDictionaryKey($model->getAttribute($local));
            }, array_keys($this->keys)));

            if (isset($dictionary[$key])) {
                $model->setRelation(
                    $relation, $this->getRelationValue($dictionary, $key, $type)
                );
            }
        }

        return $models;
    }

    protected function buildDictionary(Collection $results): array
    {
        return $results->mapToDictionary(function ($result) {
            $key = implode('_', array_map(function($foreign) use ($result) {
                return $this->getDictionaryKey($result->{$foreign});
            }, $this->keys));

            return [$key => $result];
        })->all();
    }
}
