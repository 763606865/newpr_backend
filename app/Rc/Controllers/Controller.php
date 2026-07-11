<?php

namespace App\Rc\Controllers;

use App\Exceptions\UnauthenticatedException;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Services\RcIdentityOrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $user = auth()->guard('rc')->user();
        if (! $user) {
            throw new UnauthenticatedException('Token Expired。');
        }

        return $user;
    }

    public function getPerPage(Request $request): int
    {
        return max(1, min(100, (int) $request->input('per_page', 15)));
    }

    public function currentIdentity(): ?UserIdentity
    {
        return RcIdentityOrganizationService::make()->resolveCurrentIdentity($this->user());
    }
}
