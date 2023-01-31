<?php

namespace Kroesen\LaravelAdditions\Models\Relations;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany as BaseHasMany;

class HasMany extends BaseHasMany
{
    private ?Closure $callback;

    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey, ?Closure $callback = null)
    {
        parent::__construct($query, $parent, $foreignKey, $localKey);
        $this->callback = $callback;
    }

    public function addEagerConstraints(array $models)
    {
        parent::addEagerConstraints($models);

        if($this->callback instanceof Closure){
            ($this->callback)($this->getRelationQuery());
        }
    }

    /**
     * Get the results of the relationship.
     *
     * @return array|Collection
     */
    public function getResults(): array|Collection
    {
        if($this->callback instanceof Closure){
            ($this->callback)($this->query);
        }
        return parent::getResults();
    }
}
