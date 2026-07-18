<?php

namespace App\Libs\AI;

use RuntimeException;
use Throwable;

class AIException extends RuntimeException
{
    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }
}
