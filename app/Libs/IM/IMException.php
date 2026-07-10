<?php

namespace App\Libs\IM;

use App\Libs\Exceptions\BadRequestException;
use Throwable;

class IMException extends BadRequestException
{
    public function __construct(string $message = 'IM 服务异常。', ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
