<?php

namespace App\Libs\Ocr;

use App\Libs\Exceptions\BadRequestException;
use Throwable;

class OcrException extends BadRequestException
{
    public function __construct(string $message = 'OCR 服务异常。', ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
