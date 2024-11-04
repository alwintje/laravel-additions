<?php

namespace Kroesen\LaravelAdditions\Views;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PerPage extends Component
{

    private array $sizes;
    private int $current;
    private ?string $name;

    public function __construct(array $sizes, int $current, ?string $name = null)
    {
        $this->sizes = $sizes;
        $this->current = $current;
        $this->name = $name;
    }

    public function render(): View
    {
        return view('laravel-additions::components.per-page',[
            'name' => $this->name,
            'current' => $this->current,
            'sizes' => $this->sizes
        ]);
    }
}
