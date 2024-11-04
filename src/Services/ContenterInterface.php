<?php

namespace Kroesen\LaravelAdditions\Services;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface ContenterInterface
{

    public static function create(string $cookieName, array $fields, array $defaultSorting): static;

    public function getOrDefault(string $name, mixed $default);

    public function getViewData(array $extra);

    public function handleRequest(Builder $builder, Request $request);

    public function response(View $view): Response;
}
