<?php

namespace App\Api\Controllers;

use App\Exceptions\BadRequestException;
use App\Exceptions\UnauthenticatedException;
use App\Models\Employee;
use App\Models\User;
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

    public function user(): ?User
    {
        $user = auth()->guard('api')->user();
        if (! $user) {
            throw new UnauthenticatedException('Token Expired。');
        }

        return $user;
    }

    public function employee(): ?Employee
    {
        $employee = $this->user()->token()?->responsible;
        if (! $employee) {
            throw new BadRequestException('未找到当前企业。');
        }

        return $employee;
    }
}
