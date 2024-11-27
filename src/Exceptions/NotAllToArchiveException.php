<?php

namespace Kroesen\LaravelAdditions\Exceptions;

class NotAllToArchiveException extends \Exception
{

    public function __construct(string $name, int $countInDB, int $countMustExists)
    {
        parent::__construct(sprintf('Not all %s rows are archived, missing %s of the %s', $name, $countMustExists-$countInDB, $countMustExists), 0, null);
    }
}
