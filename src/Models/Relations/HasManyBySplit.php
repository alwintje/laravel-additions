<?php

namespace Kroesen\LaravelAdditions\Models\Relations;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class HasManyBySplit extends HasMany
{

    public function __construct(
        Builder $query,
        Model $parent,
        string $foreignKey,
        string $localKey,
        protected string $delimiter = '|',
        ?Closure $callback = null
    ) {
        parent::__construct($query, $parent, $foreignKey, $localKey, $callback);
    }

    public function addConstraints()
    {
        if (static::$constraints) {
            $query = $this->getRelationQuery();

            if(!empty($this->getParentKey())){
                $query->whereIn($this->foreignKey, $this->getParentKey());
                $query->orderByRaw(sprintf('FIELD(%s, %s)', $this->foreignKey, implode(',', $this->getParentKey())));
            }else{
                $query->whereRaw('1 = 2');
            }

            $query->whereNotNull($this->foreignKey);
        }
    }

    public function addEagerConstraints(array $models): void
    {
        $whereIn = $this->whereInMethod($this->parent, $this->localKey);

        $this->whereInEager(
            $whereIn,
            $this->foreignKey,
            $this->getKeys($models, $this->localKey),
            $this->getRelationQuery()
        );
    }


    protected function getKeys(array $models, $key = null): array
    {
        $keys = [];
        foreach ($models as $model) {
            foreach ($this->getArray($key ? $model->getAttribute($key) : $model->getKey()) as $value){
                if(!empty($value)){
                    $keys[$value] = $value;
                }
            }
        }

        return array_filter($keys);
    }

    public function getParentKey()
    {
        return array_filter($this->getArray(parent::getParentKey()));
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @param  string  $type
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {

            $data = [];
            foreach ($this->getArray($model->getAttribute($this->localKey)) as $id){
                if(isset($dictionary[$id])){
                    $data = array_merge($data, $dictionary[$id]);
                }
            }

            $model->setRelation(
                $relation,
                new Collection($data)
            );
        }

        return $models;
    }

    private function getArray($data): array
    {
        if(is_array($data)){
            return $data;
        }
        return explode($this->delimiter, $data);
    }

}
