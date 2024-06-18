<?php

namespace App\Exception;

use Throwable;

class UnknownFieldException extends DomainException
{
    public function __construct(string $field, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("Unknown field '$field'", $code, $previous);
    }

}