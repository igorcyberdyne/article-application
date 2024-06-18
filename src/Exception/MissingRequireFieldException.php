<?php

namespace App\Exception;

use Throwable;

class MissingRequireFieldException extends DomainException
{
    public function __construct(string $fieldMissing = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("Missing required field" . (!empty($fieldMissing) ? ": $fieldMissing" : ""), $code, $previous);
    }

}