<?php

namespace Kroesen\LaravelAdditions\Models\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Kroesen\LaravelAdditions\Models\Relations\HasMany;
use Kroesen\LaravelAdditions\Models\Relations\HasManyThroughByMultipleFields;
use Kroesen\LaravelAdditions\Models\Relations\HasOne;
use Kroesen\LaravelAdditions\Models\Relations\HasOneByMultipleFields;
use Kroesen\LaravelAdditions\Models\Relations\HasOneThroughByMultipleFields;

trait AdvancedRelationships
{

    public function hasMany($related, $foreignKey = null, $localKey = null, ?Closure $callback = null): HasMany
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasMany(
            $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey, $callback
        );
    }

    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey, ?Closure $callback = null): HasMany
    {
        return new HasMany($query, $parent, $foreignKey, $localKey, $callback);
    }

    public function hasOne($related, $foreignKey = null, $localKey = null, ?Closure $callback = null): HasOne
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasOne(
            $instance->newQuery(),
            $this,
            $instance->getTable().'.'.$foreignKey,
            $localKey,
            $callback
        );
    }

    protected function newHasOne(Builder $query, Model $parent, $foreignKey, $localKey, ?Closure $callback = null): HasOne
    {
        return new HasOne($query, $parent, $foreignKey, $localKey, $callback);
    }

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
