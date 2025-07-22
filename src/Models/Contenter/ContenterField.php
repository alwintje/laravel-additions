<?php

namespace Kroesen\LaravelAdditions\Models\Contenter;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;

class ContenterField
{
    public const APPLICABLE_TEXT = 'text';
    public const APPLICABLE_POSITIVE_NUMBER = 'positive number';
    public const APPLICABLE_NEGATIVE_NUMBER = 'negative number';
    public const APPLICABLE_NOT_EMPTY = 'not empty';
    public bool|Closure $applicable;

    public function __construct(
        protected string    $name,
        protected mixed     $default,
        Closure|bool|string $applicable,
        public null|Closure $action,

    )
    {
        if ($this->name === 'sorting') {
            throw new Exception('Field "sorting" is reserved and cannot be used');
        }
        if ($applicable === self::APPLICABLE_TEXT) {
            $this->applicable = fn($builder, $data) => $data !== null && $data !== '';
        } elseif ($applicable === self::APPLICABLE_POSITIVE_NUMBER) {
            $this->applicable = fn($builder, $data) => $data !== null && ((int)$data) > 0;
            if ($this->action === null) {
                $this->action = fn($builder, &$data) => $data = (int)$data;
            }
        } elseif ($applicable === self::APPLICABLE_NEGATIVE_NUMBER) {
            $this->applicable = fn($builder, $data) => $data !== null && ((int)$data) < 0;
            if ($this->action === null) {
                $this->action = fn($builder, &$data) => $data = (int)$data;
            }
        } elseif ($applicable === self::APPLICABLE_NOT_EMPTY) {
            $this->applicable = fn($builder, $data) => !empty($data);
        } else {
            $this->applicable = $applicable;
        }
    }

    public static function make(
        string              $name,
        mixed               $default,
        Closure|bool|string $applicable,
        Closure             $action
    ): static
    {
        return new static($name, $default, $applicable, $action);
    }

    public static function search(string $name, Closure $action): static
    {
        return new static(
            $name,
            '',
            self::APPLICABLE_TEXT,
            fn($query, $search) => $action($query, $search === null ? null : trim($search)),
        );
    }

    public static function perPage(int $default = 15): static
    {
        return new static('perPage', $default, true, null);
    }

    public static function page(int $default = 1): static
    {
        return new static('page', $default, true, null);
    }

    public static function buttonFilter(string $name, array $options, ?Closure $action = null): static
    {

        $default = [];
        foreach ($options as $option => $enabled) {
            if (is_bool($enabled)) {
                $default[$option] = $enabled;
            } else {
                $default[$enabled] = true;
            }
        }

        if ($action === null) {
            $action = function ($builder, $mustExists, $mustNotExists) use ($name) {
                $builder
                    ->whereIn($name, $mustExists)
                    ->whereNotIn($name, $mustNotExists);
            };
        }

        $action = function ($builder, $data) use ($action) {
            $mustNotExists = [];
            $mustExists = [];
            foreach ($data as $key => $bool) {
                if ($bool === 'true') {
                    $mustExists[] = $key;
                } else {
                    $mustNotExists[] = $key;
                }
            }
            $action($builder, $mustExists, $mustNotExists);
        };

        return new static($name, $default, self::APPLICABLE_NOT_EMPTY, $action);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function isApplicable($builder, $data): bool
    {
        return (
                is_bool($this->applicable)
                && $this->applicable
            ) || (
                is_callable($this->applicable)
                && call_user_func($this->applicable, $builder, $data)
            );
    }

    public static function addToWith(EloquentBuilder|Builder|Relation $builder, string|array $relations, \Closure $callback): void
    {
        if (is_string($relations)) {
            $relations = [$relations];
        }
        $eagerLoads = $builder->getEagerLoads();
        foreach ($relations as $relation) {
            $builderCopy = $builder;
            if (!isset($eagerLoads[$relation])) {
                if (!str_contains($relation, '.')) {
                    return;
                }
                $parts = explode('.', $relation);
                $part = array_shift($parts);

                if (isset($eagerLoads[$part])) {
                    $previousClosure = $eagerLoads[$part];
                    $builderCopy->with([
                        $part => function (Relation $query) use ($parts, $callback, $previousClosure) {
                            $previousClosure($query);
                            self::addToWith($query, implode('.', $parts), $callback);
                        }
                    ]);
                }

                continue;
            }

            $previousClosure = $eagerLoads[$relation];
            $builder->with([
                $relation => function ($query) use ($callback, $previousClosure) {
                    $previousClosure($query);
                    $callback($query);
                }
            ]);
        }
    }

    public static function addToWhereHas(EloquentBuilder|Builder|Relation $builder, string|array $relations, \Closure $callback): void
    {
        if (is_string($relations)) {
            $relations = [$relations];
        }
        if (method_exists($builder, 'getQuery')) {
            $builder = $builder->getQuery();
        }
        $wheres = $builder->wheres;
        foreach ($wheres as $where) {

            if ($where['type'] === 'Exists' && isset($where['query']) && in_array($where['query']->from, $relations)) {
                /** @var Builder $query */
                $query = $where['query'];
                $callback($query);
            } elseif ($where['type'] === 'Nested') {
                self::addToWhereHas($where['query'], $relations, $callback);
            }
        }
    }

    public static function addToRawOrder(EloquentBuilder|Builder|Relation $builder, string $detect, string $after, string $toAdd): void
    {
        if(method_exists($builder, 'getQuery')){
            $builder = $builder->getQuery();
        }
        foreach ($builder->orders as $k => $order) {
            if(isset($order['type']) && $order['type'] === 'Raw' && str_contains($order['sql'], $detect)){
                $order['sql'] = str_replace($after, "$after and ($toAdd)", $order['sql']);
                $builder->orders[$k] = $order;
            }
        }
    }
}
