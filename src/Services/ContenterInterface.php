<?php

namespace Kroesen\LaravelAdditions\Services;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface ContenterInterface
{

    public static function create(string $listKey, array $fields, array $defaultSorting): static;

    public function getListData(string $keyName): array;

    public function getOrDefault(string $name, mixed $default): mixed;

    public function getViewData(array $extra): array;

    public function handleRequest(Builder $builder, Request $request): void;

    public function setBuilder(Builder $builder): void;

    public function applyFiltersToQuery(Builder $builder, array $fieldData = [], bool $save = false): void;

    public function applySortingToQuery(Builder $builder, null|array|string $sorting = null, bool $save = false): void;

    public function response(View $view): Response;

    public function formatRawSorting(string $sort, string $direction): string;
}
