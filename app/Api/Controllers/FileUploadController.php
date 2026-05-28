<?php

namespace App\Api\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class FileUploadController extends Controller
{
    /**
     * 上传文件到 OSS
     *
     * POST /api/upload
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,webp|max:128000', // 最大 128MB
            'type' => 'sometimes|in:avatar,banner,article,certificate',
        ]);

        try {
            $file = $validated['file'];
            $type = $validated['type'] ?? 'file';
            $path = $this->generatePath($type);

            // 上传文件到 OSS
            Storage::disk('oss')->put(
                $path,
                file_get_contents($file),
                ['ContentType' => $file->getMimeType()]
            );

            // 生成文件 URL
            $url = Storage::disk('oss')->url($path);

            return response()->json([
                'success' => true,
                'data' => [
                    'path' => $path,
                    'url' => $url,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '文件上传失败: '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 删除 OSS 上的文件
     *
     * DELETE /api/files/{path}
     */
    public function delete(Request $request): JsonResponse
    {
        $path = $request->input('path');

        if (! $path) {
            return response()->json([
                'success' => false,
                'message' => '文件路径不能为空',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            Storage::disk('oss')->delete($path);

            return response()->json([
                'success' => true,
                'message' => '文件删除成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '文件删除失败: '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 生成文件存储路径
     */
    private function generatePath(string $type): string
    {
        $date = now()->format('Y/m/d');
        $filename = Str::random(20).'.'.request()->file('file')->extension();

        return "uploads/{$type}/{$date}/{$filename}";
    }
}
