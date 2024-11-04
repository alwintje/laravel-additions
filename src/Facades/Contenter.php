<?php

namespace Kroesen\LaravelAdditions\Facades;

use Illuminate\Support\Facades\Facade;
use Kroesen\LaravelAdditions\Services\ContenterInterface;

/**
 * @method ContenterInterface create(string $cookieName, array $fields, array $defaultSorting)
 */
class Contenter extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        /** @see ContenterInterface */
        return 'contenter';
    }
}
