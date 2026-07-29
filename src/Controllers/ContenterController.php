<?php

namespace Kroesen\LaravelAdditions\Controllers;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Kroesen\LaravelAdditions\Services\ContenterInterface;

trait ContenterController
{

    private ?ContenterInterface $contenter = null;

    abstract protected function initializeContenter(): ContenterInterface;

    abstract protected function getQuery(Request $request): Builder;

    protected function index(): Response
    {
        return $this->contenter()->response($this->resolveView('index'));
    }

    protected function content(Request $request): Response
    {
        $this->contenter()->handleRequest($this->getQuery($request), $request);
        return $this->contenter()->response($this->resolveView('content'));
    }

    protected function contenter(): ContenterInterface
    {
        return $this->contenter ??= $this->initializeContenter();
    }

    protected function routes(): array
    {
        return [
            'index' => null,
            'content' => null,
            'export' => null,
            'statistics' => null,
        ];
    }

    private function getRoutes(): array
    {
        return Cache::rememberForever(static::class.'-routes', fn () =>
            $this->findRoutes($this->routes())
        );
    }

    private function findRoutes(array $routes): array
    {
        $checks = $this->getChecks();
        $routes = $this->findExplicitRoutes($routes, $checks);
        return $this->findConventionRoutes($routes, $checks);
    }

    private function getChecks(): Collection
    {
        $classPath = Str::after(static::class, 'App\\Http\\Controllers\\');

        $resource = Str::of($classPath)
            ->replaceLast('Controller', '')
            ->explode('\\')
            ->map(fn ($part) => Str::snake($part))
            ->unique();
        $pluralResource = $resource->map(fn ($part) => Str::plural($part));
        $singular = $resource->implode('.');
        $plural = $pluralResource->implode('.');

        return collect([
            $singular,
            str_replace('_', '-', $singular),
            $plural,
            str_replace('_', '-', $plural),
        ])->map(fn ($path) => $path . '.');
    }

    private function resolveView(string $name): \Illuminate\View\View
    {
        $view = Cache::rememberForever(static::class . '-view-' . $name, function () use ($name) {
            $checks = $this->getChecks()->map(fn($check) => $check.$name);

            foreach ($checks as $check) {
                if(View::exists($check)) {
                    return $check;
                }
            }

            throw new Exception(
                ucfirst($name) . ' view not found, searched: "'.$checks->implode('", "').'"'
            );

        });

        return view($view);
    }

    private function findExplicitRoutes(array $routes, Collection $checks): array
    {
        foreach ($checks as $check) {

            if (!in_array(null, $routes, true)) {
                break;
            }

            foreach ($routes as $action => $_) {
                if($_ !== null){
                    continue;
                }
                $name = $check.$action;
                if(\Route::has($check.$action)){
                    $route = \Route::get($name);

                    if($route->getControllerClass() !== static::class){
                        continue;
                    }

                    $routes[$action] = $check.$action;
                    break;
                }
            }
        }
        return $routes;
    }

    private function findConventionRoutes(array $routes, Collection $checks): array
    {
        /** @var Route $route */
        foreach (\Route::getRoutes() as $route){

            if (!in_array(null, $routes, true)) {
                break;
            }

            $name = strtolower($route->getName() ?? '');

            if($route->getControllerClass() !== static::class){
                continue;
            }

            if (!$checks->first(fn ($check) => str_contains($name, $check))) {
                continue;
            }


            foreach ($routes as $action => $_) {
                if($_ !== null){
                    continue;
                }
                if (str_ends_with($name, $action)) {
                    $routes[$action] = $name;
                    break;
                }
            }

        }
        return $routes;
    }
}
