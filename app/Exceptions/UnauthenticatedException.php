<?php

namespace App\Exceptions;

use Throwable;

class UnauthenticatedException extends HttpException
{
    public function __construct(string $message = 'Unauthenticated.', ?Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
