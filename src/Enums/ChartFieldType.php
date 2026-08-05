<?php

namespace Kroesen\LaravelAdditions\Enums;

enum ChartFieldType: string
{
    case NONE = 'NONE';
    case LABEL = 'LABEL';
    case COUNT = 'COUNT';
    case SUM = 'SUM';
    case AVG = 'AVG';
    case MIN = 'MIN';
    case MAX = 'MAX';
    case MONTH = 'MONTH';
    case YEAR = 'YEAR';
    case DATE = 'DATE';

    public function expression(string $field): string
    {
        return match ($this) {
            self::NONE, self::LABEL => $field,
            self::COUNT => "COUNT($field)",
            self::SUM   => "SUM($field)",
            self::AVG   => "AVG($field)",
            self::MIN   => "MIN($field)",
            self::MAX   => "MAX($field)",
            self::MONTH => "MONTH($field)",
            self::YEAR  => "YEAR($field)",
            self::DATE  => "DATE($field)",
        };
    }

    public function isAggregate(): bool
    {
        return match ($this) {
            self::COUNT,
            self::SUM,
            self::AVG,
            self::MIN,
            self::MAX => true,
            self::NONE,
            self::LABEL,
            self::MONTH,
            self::YEAR,
            self::DATE => false,
        };
    }

}
