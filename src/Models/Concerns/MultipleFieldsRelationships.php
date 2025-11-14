<?php

namespace Kroesen\LaravelAdditions\Models\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Kroesen\LaravelAdditions\Models\Relations\HasManyThroughByMultipleFields;
use Kroesen\LaravelAdditions\Models\Relations\HasOneByMultipleFields;
use Kroesen\LaravelAdditions\Models\Relations\HasOneThroughByMultipleFields;

trait MultipleFieldsRelationships
{

    public function hasOneByMultipleFields($related, array $keys = null, ?Closure $callback = null): HasOneByMultipleFields
    {
        $instance = $this->newRelatedInstance($related);

        return $this->newHasOneByMultipleFields(
            $instance->newQuery(),
            $this,
            $keys,
            $instance->getTable(),
            $callback
        );
    }

    protected function newHasOneByMultipleFields(Builder $query, Model $parent, array $keys, string $foreignTable, ?Closure $callback = null): HasOneByMultipleFields
    {
        return new HasOneByMultipleFields($query, $parent, $keys, $foreignTable, $callback);
    }

    public function hasOneThroughByMultipleFields($related, $through, array $firstKeys, array $foreignKeys, ?Closure $callback = null): HasOneThroughByMultipleFields
    {
        $through = $this->newRelatedThroughInstance($through);

        return $this->newHasOneThroughByMultipleFields(
            $this->newRelatedInstance($related)->newQuery(),
            $this,
            $through,
            $firstKeys,
            $foreignKeys,
            $callback,
        );
    }

    protected function newHasOneThroughByMultipleFields(Builder $query, Model $farParent, Model $throughParent, array $firstKeys, array $foreignKeys, ?Closure $callback = null): HasOneThroughByMultipleFields
    {
        return new HasOneThroughByMultipleFields($query, $farParent, $throughParent, $firstKeys, $foreignKeys, $callback);
    }

    public function hasManyThroughByMultipleFields($related, $through, array $firstKeys, array $foreignKeys, ?Closure $callback = null): HasManyThroughByMultipleFields
    {
        $through = $this->newRelatedThroughInstance($through);

        return $this->newHasManyThroughByMultipleFields(
            $this->newRelatedInstance($related)->newQuery(),
            $this,
            $through,
            $firstKeys,
            $foreignKeys,
            $callback,
        );
    }

    protected function newHasManyThroughByMultipleFields(Builder $query, Model $farParent, Model $throughParent, array $firstKeys, array $foreignKeys, ?Closure $callback = null): HasManyThroughByMultipleFields
    {
        return new HasManyThroughByMultipleFields($query, $farParent, $throughParent, $firstKeys, $foreignKeys, $callback);
    }

}
