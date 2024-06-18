<?php

namespace App\Exception;

use Throwable;

class SearchException extends DomainException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(!empty($message) ? $message : "Search error", $code, $previous);
    }
}