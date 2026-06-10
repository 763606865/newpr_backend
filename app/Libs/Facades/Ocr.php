<?php

namespace App\Libs\Facades;

use App\Libs\Ocr\Contracts\OcrDriver;
use App\Libs\Ocr\Data\BusinessLicenseResult;
use App\Libs\Ocr\Data\RecognizeResult;
use Illuminate\Support\Facades\Facade;

/**
 * @method static OcrDriver driver(?string $driver = null)
 * @method static RecognizeResult recognizeGeneralByUrl(string $url, ?string $driver = null)
 * @method static RecognizeResult recognizeGeneralByContent(string $content, ?string $driver = null)
 * @method static BusinessLicenseResult recognizeBusinessLicenseByUrl(string $url, ?string $driver = null)
 * @method static BusinessLicenseResult recognizeBusinessLicenseByContent(string $content, ?string $driver = null)
 *
 * @see \App\Libs\Ocr\Ocr
 */
class Ocr extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Libs\Ocr\Ocr::class;
    }
}
