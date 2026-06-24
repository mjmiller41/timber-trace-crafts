<?php

namespace App\Exceptions;

use RuntimeException;

class EtsyApiException extends RuntimeException
{
    public function __construct(string $message, public readonly int $statusCode = 0)
    {
        parent::__construct($message);
    }
}
