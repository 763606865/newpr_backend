<?php

namespace App\B\Controllers;

use App\Exceptions\BadRequestException;
use App\Exceptions\UnauthenticatedException;
use App\Models\BUser;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
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

    public function user(): ?BUser
    {
        $user = auth()->guard('b')->user();
        if (! $user) {
            throw new UnauthenticatedException('Token Expired。');
        }

        return $user;
    }

    public function company(): ?Company
    {
        $company = $this->user()->token()?->responsible;
        if (! $company) {
            throw new BadRequestException('未找到当前企业。');
        }

        return $company;
    }
}
