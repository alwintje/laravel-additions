<?php

namespace Kroesen\LaravelAdditions\Models\Chart;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Kroesen\LaravelAdditions\Enums\ChartFieldType;

class ChartData implements ChartDataInterface
{

    public function __construct(
        protected string $name,
        protected null|string|ChartFieldInterface $field = null,
        protected ChartFieldType $type = ChartFieldType::NONE,
        protected ?string $groupBy = null,
    ) {
    }

    public static function make(
        string $name,
        null|string|ChartFieldInterface $field = null,
        ?ChartFieldType $type = ChartFieldType::NONE,
        ?string $groupBy = null,
    ): static {
        return new static($name, $field, $type, $groupBy);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function setType(ChartFieldType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): ChartFieldType
    {
        return $this->type;
    }

    public static function count(string $name, string $field): static
    {
        return static::make($name, $field, ChartFieldType::COUNT);
    }

    public static function sum(string $name, string $field): static
    {
        return static::make($name, $field, ChartFieldType::SUM);
    }

    public function getGroupBy(): ?string
    {
        return $this->groupBy;
    }

    public function handleQuery(Builder $query): static
    {
        if($this->field !== null){
            $query->addSelect(DB::raw("{$this->type->expression($this->field)} as '$this->name'"));
        }

        if($this->groupBy !== null || $this->getType() === ChartFieldType::LABEL){
            $query->groupBy(DB::raw($this->groupBy ?? $this->type->expression($this->field)));
        }

        return $this;
    }

    public function getData(array|Collection $results): array
    {
        if($this->type === ChartFieldType::LABEL){
            $labels = [];
            foreach ($results as $item) {
                $labels[] = $item->getAttribute($this->name);
            }
            return $labels;
        }

        if($results instanceof Collection){
            return $results->pluck($this->name)->toArray();
        }
        return array_column($results, $this->name);
    }
}
