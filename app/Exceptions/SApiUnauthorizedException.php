<?php

namespace App\Exceptions;

use Throwable;

class SApiUnauthorizedException extends HttpException
{
    public function __construct(string $message = 'SApi 鉴权失败。', ?Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
