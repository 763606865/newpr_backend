<?php

namespace App\Libs\Amap;

use App\Libs\Exceptions\BadRequestException;
use Throwable;

class AmapException extends BadRequestException
{
    public function __construct(string $message = '高德地图服务异常。', ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
