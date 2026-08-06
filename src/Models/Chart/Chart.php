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
    protected const TYPES = [
        'bar',
        'line',
        'doughnut',
        'pie',
        'polarArea',
        'radar',
    ];

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
        if($view instanceof View){
            $this->view($view);
        }elseif(in_array($view, self::TYPES, true)){
            $this->type($view);
        }else{
            $this->view($view);
        }
        $this->id = Str::uuid()->toString();
    }

    public static function make(
        string|View         $view,
        ?string             $title = null,
        ?ChartDataInterface $labels = null,
        array               $datasets = [],
        string|array        $orderBy = [],
    ): static {
        $self = new static($view);

        $self->title = $title;
        $self->labels = $labels;
        if($labels !== null && $labels->getType() === ChartFieldType::NONE){
            $labels->setType(ChartFieldType::LABEL);
        }
        foreach ($datasets as $dataset) {
            $self->addDataset($dataset);
        }

        $self->orderBy($orderBy);

        return $self;
    }

    public static function bar(?string $title = null): static
    {
        return static::make('bar', $title);
    }

    public static function line(?string $title = null): static
    {
        return static::make('line', $title);
    }

    public static function doughnut(?string $title = null): static
    {
        return static::make('doughnut', $title);
    }

    public static function pie(?string $title = null): static
    {
        return static::make('pie', $title);
    }

    public static function polarArea(?string $title = null): static
    {
        return static::make('polarArea', $title);
    }

    public static function radar(?string $title = null): static
    {
        return static::make('radar', $title);
    }

    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function type(string $type): static
    {
        $this->view(view(
            'laravel-additions::components.chart.base',
            ['type' => $type]
        ));

        return $this;
    }

    public function view(View|string $view): static
    {
        $this->view = $view instanceof View ? $view : view($view);
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function labels(ChartDataInterface $labels): static
    {
        $this->labels = $labels;
        $labels->setType(ChartFieldType::LABEL);
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

    public function orderBy(array|string|ChartFieldInterface $orderBy): static
    {
        if($orderBy instanceof ChartFieldInterface) {
            $orderBy = $orderBy->order();
        }
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
            $orderBy = $orderBy instanceof ChartFieldInterface ? $orderBy->order() : $orderBy;

            $query->orderBy(DB::raw($orderBy), $direction);
            $query->groupBy(DB::raw($orderBy));
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
