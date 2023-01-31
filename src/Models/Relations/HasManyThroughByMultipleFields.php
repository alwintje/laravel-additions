<?php

namespace Kroesen\LaravelAdditions\Models\Relations;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class HasManyThroughByMultipleFields extends HasManyThrough
{
    protected ?Closure $callback;
    protected array $firstKeys;
    protected array $foreignKeys;

    public function __construct(Builder $query, Model $farParent, Model $throughParent, array $firstKeys, array $foreignKeys, ?Closure $callback = null)
    {
        $secondKey = array_key_first($firstKeys);
        $firstKey = $firstKeys[$secondKey];
        $localKey = array_key_first($foreignKeys);
        $secondLocalKey = $foreignKeys[$localKey];
        $this->firstKeys = $firstKeys;
        $this->foreignKeys = $foreignKeys;
        $this->callback = $callback;
        parent::__construct($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    public function addConstraints()
    {
        $this->performJoin();

        if (static::$constraints) {
            foreach ($this->firstKeys as $localKey => $middleKey){
                $this->query->where($this->throughParent->qualifyColumn($middleKey), $this->farParent->getAttribute($localKey));
            }
        }
    }

    protected function performJoin(Builder $query = null)
    {
        $query = $query ?: $this->query;

        $throughTable = $this->throughParent->getTable();
        $foreignTable = $query->getModel()->getTable();
        $query->join($throughTable, function($join) use ($foreignTable, $throughTable) {
            foreach ($this->foreignKeys as $foreignKey => $throughKey){
                $join->on($throughTable.'.'.$throughKey, '=', $foreignTable.'.'.$foreignKey);
            }
        });
        if ($this->throughParentSoftDeletes()) {
            $query->withGlobalScope('SoftDeletableHasManyThrough', function ($query) {
                $query->whereNull($this->throughParent->getQualifiedDeletedAtColumn());
            });
        }
    }

    public function addEagerConstraints(array $models)
    {

        foreach($this->firstKeys as $localKey => $foreignKey){
            $whereIn = $this->whereInMethod($this->farParent, $localKey);

            $this->query->{$whereIn}(
                $this->throughParent->getTable().'.'.$foreignKey, $this->getKeys($models, $localKey)
            );
        }

        if($this->callback instanceof Closure){
            ($this->callback)($this->query);
        }
    }

    protected function shouldSelect(array $columns = ['*']): array
    {
        if ($columns == ['*']) {
            $columns = [$this->related->getTable().'.*'];
        }

        $concat = 'concat('.implode(",'_',", array_map(function($key){
            return $this->throughParent->qualifyColumn($key);
        }, $this->firstKeys)).')';

        return array_merge($columns, [$this::raw($concat.' as laravel_through_key')]);//[$concat.' as laravel_through_key']);
    }

    public function getResults()
    {
//        dump($this->query);
        if($this->callback instanceof Closure){
            ($this->callback)($this->query);
        }
        return parent::getResults();
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
                $model->setRelation(
                    $relation, $this->related->newCollection($dictionary[$key])
                );
            }
        }

        return $models;
    }

}
