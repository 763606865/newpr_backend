<?php

namespace App\Rc\Controllers;

use App\Rc\Requests\UploadStoreRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * 上传文件到 OSS
     *
     * POST /rc/upload
     */
    public function upload(UploadStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $file = $validated['file'];
        $type = $validated['type'] ?? 'file';
        $path = $this->generatePath($type, $file->extension());

        try {
            Storage::disk('oss')->put(
                $path,
                file_get_contents($file->getRealPath()),
                ['ContentType' => $file->getMimeType()]
            );

            return $this->success([
                'path' => $path,
                'url' => Storage::disk('oss')->url($path),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        } catch (\Throwable $exception) {
            return $this->error('文件上传失败: '.$exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 删除 OSS 上的文件
     *
     * DELETE /rc/files
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
        ]);

        try {
            Storage::disk('oss')->delete($validated['path']);

            return $this->success(['message' => '文件删除成功。']);
        } catch (\Throwable $exception) {
            return $this->error('文件删除失败: '.$exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function generatePath(string $type, string $extension): string
    {
        $date = now()->format('Y/m/d');
        $filename = Str::random(20).'.'.strtolower($extension);

        return "uploads/rc/{$type}/{$date}/{$filename}";
    }
}
