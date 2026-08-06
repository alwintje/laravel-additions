<?php

namespace Kroesen\LaravelAdditions\Models\Chart;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Kroesen\LaravelAdditions\Enums\ChartFieldType;

class Chart implements ChartInterface
{

    private string           $id;
    private null|string      $title      = null;
    private string|View      $view;

    private null|ChartDataInterface $labels = null;

    /** @var array|ChartDataInterface[] */
    private array            $datasets       = [];


    private null|Closure     $modifyQuery      = null;
    private array            $orderBy    = [];

    public function __construct(string|View $view)
    {
        $this->view($view);
        $this->id = Str::uuid()->toString();
    }

    public static function make(
        string|View         $view,
        ?string             $title = null,
        ?ChartDataInterface $labels = null,
        array               $datasets = [],
        string|array        $orderBy = [],
    ): static
    {
        $self = new static($view);

        $self->title = $title;
        $self->labels = $labels;
        if($labels->getType() === ChartFieldType::NONE){
            $labels->setType(ChartFieldType::LABEL);
        }
        foreach ($datasets as $dataset) {
            $self->addDataset($dataset);
        }

        $self->setOrderBy($orderBy);

        return $self;
    }

    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function view(View|string $view): static
    {
        if(is_string($view)) {
            $this->view = match ($view) {
                'bar' => view('laravel-additions::components.chart.base', ['type' => 'bar']),
                'line' => view('laravel-additions::components.chart.base', ['type' => 'line']),
                'doughnut' => view('laravel-additions::components.chart.base', ['type' => 'doughnut']),
                'pie' => view('laravel-additions::components.chart.base', ['type' => 'pie']),
                'polarArea' => view('laravel-additions::components.chart.base', ['type' => 'polarArea']),
                'radar' => view('laravel-additions::components.chart.base', ['type' => 'radar']),
//                'mixed' => view('laravel-additions::components.chart.chart', ['type' => 'mixed']),
//                'area' => view('laravel-additions::components.chart.chart', ['type' => 'area']),
//                'bubble' => view('laravel-additions::components.chart.chart', ['type' => 'bubble']),
//                'scatter' => view('laravel-additions::components.chart.chart', ['type' => 'scatter']),
                default => view($view),
            };
        }else{
            $this->view = $view;
        }
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function chartType(string $type = 'bar'): static
    {
        $this->chartType = $type;
        return $this;
    }

    public function addDataset(ChartDataInterface $dataset): static
    {
        $this->datasets[$dataset->getName()] = $dataset;
        return $this;
    }

    public function removeDataset(string|ChartDataInterface $axis): static
    {
        unset($this->datasets[$axis->getName() ?? $axis]);
        return $this;
    }

    public function modifyQuery(Closure $function): static
    {
        $this->modifyQuery = $function;
        return $this;
    }

    public function setOrderBy(array|string $orderBy): static
    {
        if(is_string($orderBy)){
            $this->orderBy = [$orderBy => 'asc'];
        }else{
            $this->orderBy = $orderBy;
        }
        return $this;
    }

    public function build(Builder $query): View
    {
        $query = clone $query;

        // Clear query
        $query
            ->select([]) // Remove selects
            ->setEagerLoads([]) // Remove relations
            ->reorder() // Remove order
        ;

        $this->labels->handleQuery($query);

        foreach ($this->datasets as $dataset) {
            $dataset->handleQuery($query);
        }

        if(null !== $this->modifyQuery) {
            ($this->modifyQuery)($query, $this);
        }

        foreach ($this->orderBy as $orderBy => $direction) {
            $query->orderBy(DB::raw($orderBy), $direction);
        }

        return $this->view->with([
            'id' => $this->id,
            'title' => $this->title,
            'results' => $query->get(),
            'labels' => $this->labels,
            'datasets' => $this->datasets,
        ]);
    }

    public function __clone(): void
    {
        $this->id = Str::uuid()->toString();
    }
}
