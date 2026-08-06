<?php

namespace Kroesen\LaravelAdditions\Models\Chart;

class ChartField implements ChartFieldInterface
{
    protected bool $ordering = false;

    public function __construct(
        protected string  $select,
        protected ?string $order = null
    ){
        if($this->order === null){
            $this->order = $this->select;
        }
    }

    public static function normal(string $field): static
    {
        return new static($field);
    }

    public static function if(string $condition, string $true, string $false): static
    {
        return new static("IF($condition, $true, $false)");
    }

    public static function date(string $field, string $format, ?string $formatWhenOrdering = null): static
    {
        if($formatWhenOrdering === null){
            $formatWhenOrdering = $format;
        }
        return new static(
            "DATE_FORMAT($field, '$format')",
            "DATE_FORMAT($field, '$formatWhenOrdering')");
    }

    public static function days(string $field): static
    {
        return static::date($field, '%d-%m-%Y', '%Y-%m-%d');
    }

    public static function weeks(string $field): static
    {
        return static::date($field, '%v-%x', '%x-%v');
    }

    public static function months(string $field): static
    {
        return static::date($field, '%m-%Y', '%Y-%m');
    }

    public static function years(string $field): static
    {
        return static::date($field, '%Y');
    }

    public static function count(string $field): static
    {
        return new static("COUNT($field)");
    }

    public static function sum(string $field): static
    {
        return new static("SUM($field)");
    }

    public static function avg(string $field): static
    {
        return new static("AVG($field)");
    }

    public static function min(string $field): static
    {
        return new static("MIN($field)");
    }

    public static function max(string $field): static
    {
        return new static("MAX($field)");
    }

    public function order(): string
    {
        return $this->order;
    }

    public function __toString(): string
    {
        return $this->select;
    }

}
