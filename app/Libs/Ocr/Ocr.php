<?php

namespace App\Libs\Ocr;

use App\Libs\Ocr\Contracts\OcrDriver;
use App\Libs\Ocr\Data\BusinessLicenseResult;
use App\Libs\Ocr\Data\RecognizeResult;

class Ocr
{
    public function __construct(protected OcrManager $manager) {}

    public function driver(?string $driver = null): OcrDriver
    {
        return $this->manager->driver($driver);
    }

    public function recognizeGeneralByUrl(string $url, ?string $driver = null): RecognizeResult
    {
        return $this->driver($driver)->recognizeGeneralByUrl($url);
    }

    public function recognizeGeneralByContent(string $content, ?string $driver = null): RecognizeResult
    {
        return $this->driver($driver)->recognizeGeneralByContent($content);
    }

    public function recognizeBusinessLicenseByUrl(string $url, ?string $driver = null): BusinessLicenseResult
    {
        return $this->driver($driver)->recognizeBusinessLicenseByUrl($url);
    }

    public function recognizeBusinessLicenseByContent(string $content, ?string $driver = null): BusinessLicenseResult
    {
        return $this->driver($driver)->recognizeBusinessLicenseByContent($content);
    }
}
