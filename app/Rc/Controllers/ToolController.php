<?php

namespace App\Rc\Controllers;

use App\Libs\Facades\Ocr;
use App\Libs\Ocr\OcrException;
use App\Rc\Requests\OcrBusinessLicenseRequest;
use App\Resources\Rc\RcOcrBusinessLicenseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ToolController extends Controller
{
    /**
     * 营业执照 OCR 识别
     *
     * POST /rc/tools/ocr/business-license
     *
     * @throws \Exception
     */
    public function recognizeBusinessLicense(OcrBusinessLicenseRequest $request): JsonResponse
    {
        $this->user();

        $validated = $request->validated();

        try {
            if ($request->hasFile('file')) {
                $file = $validated['file'];
                $result = Ocr::recognizeBusinessLicenseByContent(
                    file_get_contents($file->getRealPath()),
                );
            } else {
                $result = Ocr::recognizeBusinessLicenseByUrl((string) $validated['url']);
            }
        } catch (OcrException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success((new RcOcrBusinessLicenseResource($result))->resolve($request));
    }
}
