<?php

namespace App\Libs\Exceptions;

use Throwable;

class BadRequestException extends HttpException
{
    public function __construct(string $message = '服务器内部错误.', ?Throwable $previous = null)
    {
        parent::__construct($message, 500, $previous);
    }
}
