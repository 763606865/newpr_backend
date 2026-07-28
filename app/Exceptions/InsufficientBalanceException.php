<?php

namespace App\Exceptions;

use Throwable;

class InsufficientBalanceException extends HttpException
{
    public function __construct(string $message = '余额不足，请及时充值.', ?Throwable $previous = null)
    {
        parent::__construct($message, 423, $previous);
    }
}
