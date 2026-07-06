<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function resolveCityCode(Request $request): ?string
    {
        $cityCode = $request->string('city_code')->toString();

        return $cityCode !== '' ? $cityCode : null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function resolvePerPage(array $validated, int $default = 15, int $max = 50): int
    {
        $perPage = (int) ($validated['per_page'] ?? $default);

        return max(1, min($perPage, $max));
    }

    /**
     * @throws \Exception
     */
    protected function success(mixed $data = []): JsonResponse
    {
        return api_response($data);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    protected function error(string $message, int $status, array $errors = []): JsonResponse
    {
        $now = microtime(true);
        $rv = [
            'code' => $status,
            'message' => $message,
            'meta' => [
                'timestamp' => $now,
                'response_time' => $now - LARAVEL_START,
            ],
        ];
        if (config('app.debug') && ! empty($errors)) {
            $rv['errors'] = $errors;
        }

        return response()->json($rv);
    }
}
