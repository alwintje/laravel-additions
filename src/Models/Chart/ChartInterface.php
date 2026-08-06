<?php

namespace Kroesen\LaravelAdditions\Models\Chart;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

interface ChartInterface
{
    public static function make(
        string|View $view,
        ?string     $title = null,
        ?ChartData  $labels = null,
        array       $datasets = [],
    ): static;

    public function id(string $id): static;

    public function view(View|string $view): static;

    public function title(string $title): static;

    public function chartType(string $type = 'bar'): static;

    public function addDataset(ChartDataInterface $dataset): static;

    public function removeDataset(string|ChartDataInterface $axis): static;

    public function modifyQuery(Closure $function): static;

    public function setOrderBy(array|string $orderBy): static;

    public function build(Builder $query): View;
}
