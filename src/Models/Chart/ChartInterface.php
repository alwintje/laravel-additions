<?php

namespace Kroesen\LaravelAdditions\Models\Chart;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

interface ChartInterface
{
    public static function make(
        string|View         $view,
        ?string             $title = null,
        ?ChartDataInterface $labels = null,
        array               $datasets = [],
        string|array        $orderBy = [],
    ): static;

    public static function bar(?string $title = null): static;
    public static function line(?string $title = null): static;
    public static function doughnut(?string $title = null): static;
    public static function pie(?string $title = null): static;
    public static function polarArea(?string $title = null): static;
    public static function radar(?string $title = null): static;

    public function id(string $id): static;

    public function type(string $type): static;

    public function view(View|string $view): static;


    public function title(string $title): static;

    public function labels(ChartDataInterface $labels): static;

    public function datasets(array $datasets): static;

    public function addDataset(ChartDataInterface $dataset): static;

    public function removeDataset(string|ChartDataInterface $axis): static;

    public function modifyQuery(Closure $function): static;

    public function orderBy(array|string|ChartFieldInterface $orderBy): static;

    public function build(Builder $query): View;
}
