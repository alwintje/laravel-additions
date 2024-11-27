<?php

namespace Kroesen\LaravelAdditions\Helpers;

class Condition
{

    public static function byDate($query, $options): void
    {
        $query->where($options['field'] ?? 'created_at', '<', now()->subDays($options['days'] ?? 30)->toDateTimeString());
    }

}
