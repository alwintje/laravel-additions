<?php

namespace Kroesen\LaravelAdditions\Models\Chart;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Kroesen\LaravelAdditions\Enums\ChartFieldType;

interface ChartDataInterface
{

    public static function make(
        string          $name,
        string          $field,
        ChartFieldType  $type = ChartFieldType::NONE,
        ?string         $groupBy = null,
    ): static;

    public function getName(): string;

    public function getField(): ?string;

    public function setType(ChartFieldType $type): static;

    public function getType(): ChartFieldType;

    public function getGroupBy(): ?string;

    public function handleQuery(Builder $query): static;

    public function getData(array|Collection $results): array;

}
