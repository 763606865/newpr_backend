<?php

namespace App\Libs\Ocr\Contracts;

use App\Libs\Ocr\Data\BusinessLicenseResult;
use App\Libs\Ocr\Data\RecognizeResult;
use App\Libs\Ocr\OcrException;

interface OcrDriver
{
    /**
     * 通用文字识别（图片 URL）。
     *
     * @throws OcrException
     */
    public function recognizeGeneralByUrl(string $url): RecognizeResult;

    /**
     * 通用文字识别（图片二进制）。
     *
     * @throws OcrException
     */
    public function recognizeGeneralByContent(string $content): RecognizeResult;

    /**
     * 营业执照识别（图片 URL）。
     *
     * @throws OcrException
     */
    public function recognizeBusinessLicenseByUrl(string $url): BusinessLicenseResult;

    /**
     * 营业执照识别（图片二进制）。
     *
     * @throws OcrException
     */
    public function recognizeBusinessLicenseByContent(string $content): BusinessLicenseResult;
}
