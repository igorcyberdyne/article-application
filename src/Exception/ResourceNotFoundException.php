<?php

namespace App\Exception;

use Throwable;

class ResourceNotFoundException extends DomainException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(!empty($message) ? $message : "Resource not found", $code, $previous);
    }

}