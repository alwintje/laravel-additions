<?php

namespace Kroesen\LaravelAdditions\Models\Chart;

interface ChartFieldInterface
{
    public static function normal(string $field): static;

    public static function if(string $condition, string $true, string $false): static;

    public static function date(string $field, string $format, ?string $formatWhenOrdering = null): static;

    public static function days(string $field): static;

    public static function weeks(string $field): static;

    public static function months(string $field): static;

    public static function years(string $field): static;

    public static function count(string $field): static;

    public static function sum(string $field): static;

    public static function avg(string $field): static;

    public static function min(string $field): static;

    public static function max(string $field): static;

    public function order(): string;

}
