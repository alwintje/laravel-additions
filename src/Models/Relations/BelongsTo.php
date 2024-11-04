<?php

namespace Kroesen\LaravelAdditions\Models\Relations;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BaseBelongsTo;

class BelongsTo extends BaseBelongsTo
{
    private ?Closure $callback;

    public function __construct(Builder $query, Model $child, $foreignKey, $ownerKey, $relationName, ?Closure $callback = null)
    {
        parent::__construct($query, $child, $foreignKey, $ownerKey, $relationName);
        $this->callback = $callback;
    }

    public function addEagerConstraints(array $models)
    {
        parent::addEagerConstraints($models);

        if($this->callback instanceof Closure){
            ($this->callback)($this->getRelationQuery());
        }
    }

    public function getResults(): mixed
    {
        if($this->callback instanceof Closure){
            ($this->callback)($this->query);
        }
        return parent::getResults();
    }
}
