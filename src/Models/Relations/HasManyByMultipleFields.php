<?php

namespace Kroesen\LaravelAdditions\Models\Relations;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class HasManyByMultipleFields extends Relation
{

    public function __construct(
        Builder $query,
        Model $parent,
        protected array $keys,
        protected ?Closure $callback = null
    ) {
        parent::__construct($query, $parent);
    }

    public function addConstraints(): void
    {
        if (static::$constraints) {
            $keys = $this->keys;

            $this->query->where(function ($query) use ($keys): void {
                foreach ($keys as $foreignKey => $localKey) {
                    $query->orWhere(function ($query) use ($localKey, $foreignKey): void {
                        $query->where($foreignKey, '=', $localKey)
                            ->whereNotNull($foreignKey);
                    });
                }
            });
        }
    }

    public function getQualifiedParentKeyName(): array
    {
        $keys = [];
        foreach ($this->keys as $key) {
            $keys[$key] = $this->parent->qualifyColumn($key);
        }
        return $keys;
    }

    public function addEagerConstraints(array $models): void
    {
        $keys = $this->keys;

        $orWhereIn = [];
        foreach ($keys as $localKey) {
            $orWhereIn[$localKey] = $this->orWhereInMethod($this->parent, $localKey);
        }

        $this->query->where(function ($query) use ($keys, $models, $orWhereIn): void {
            foreach ($keys as $foreignKey => $localKey) {
                $query->{$orWhereIn[$localKey]}($foreignKey, $this->getKeys($models, $localKey));
            }
        });

        if($this->callback instanceof Closure){
            ($this->callback)($this->getRelationQuery());
        }
    }

    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        $keys = $this->keys;

        return $query->select($columns)->where(function ($query) use ($keys): void {
            foreach ($keys as $foreignKey => $localKey) {
                $query->orWhere(function ($query) use ($localKey, $foreignKey): void {
                    $query->whereColumn($this->getQualifiedParentKeyName()[$localKey], '=', $foreignKey)
                        ->whereNotNull($foreignKey);
                });
            }
        });
    }

    protected function orWhereInMethod(Model $model, string $key): string
    {
        return $model->getKeyName() === last(explode('.', $key))
        && in_array($model->getKeyType(), ['int', 'integer'])
            ? 'orWhereIntegerInRaw'
            : 'orWhereIn';
    }

    public function initRelation(array $models, $relation): array
    {
        // Info: From HasMany class
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    public function match(array $models, Collection $results, $relation): array
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {
            $data = $this->related->newCollection();
            foreach ($this->keys as $foreignKey => $localKey) {
                if (isset($dictionary[$foreignKey][$key = $model->getAttribute($localKey)])) {
                    $data = $data->merge($dictionary[$foreignKey][$key]);
                }
            }
            $model->setRelation($relation, $data->unique($this->related->getKeyName()));
        }
        return $models;
    }

    protected function buildDictionary(Collection $results): array
    {

        $dictionary = array_fill_keys(array_keys($this->keys), []);

        foreach ($results as $result) {
            foreach ($this->keys as $foreignKey => $localKey) {

                $foreignKeyValue = $result->{$foreignKey};
                if (! isset($dictionary[$foreignKey][$foreignKeyValue])) {
                    $dictionary[$foreignKey][$foreignKeyValue] = [];
                }

                $dictionary[$foreignKey][$foreignKeyValue][] = $result;
            }
        }

        return $dictionary;
    }

    public function getResults()
    {
        if($this->callback instanceof Closure){
            ($this->callback)($this->query);
        }
        return $this->get();
    }

}
