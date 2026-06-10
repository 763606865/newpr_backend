<?php

namespace App\SApi\Controllers;

use App\Exceptions\SApiUnauthorizedException;
use App\Models\SApi\Client;
use Illuminate\Database\Eloquent\Builder;
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

    protected function client(): Client
    {
        $client = request()->attributes->get('sapi_client');

        if (! $client instanceof Client) {
            throw new SApiUnauthorizedException('SApi 客户端未解析。');
        }

        return $client;
    }

    protected function paginationEnabled(Request $request): bool
    {
        if ($request->exists('pagination_enabled')) {
            return $request->boolean('pagination_enabled');
        }

        return (bool) config('sapi.pagination_enabled', true);
    }

    protected function getPerPage(Request $request, int $default = 15, int $max = 100): int
    {
        return max(1, min($max, (int) $request->input('per_page', $default)));
    }

    /**
     * @param  callable(mixed): array<string, mixed>  $transformer
     */
    protected function indexDataResponse(Request $request, Builder $query, callable $transformer): JsonResponse
    {
        if ($this->paginationEnabled($request)) {
            return $this->success(
                $query->paginate($this->getPerPage($request))->through($transformer),
            );
        }

        return $this->success(
            $query->get()->map($transformer)->values(),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function applyCreatedBetween(Builder $query, array $validated, ?string $table = null): void
    {
        $table ??= $query->getModel()->getTable();

        if (filled($validated['created_from'] ?? null)) {
            $query->where($table.'.created_at', '>=', $validated['created_from']);
        }

        if (filled($validated['created_to'] ?? null)) {
            $query->where($table.'.created_at', '<=', $validated['created_to']);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function applyUpdatedBetween(Builder $query, array $validated, ?string $table = null): void
    {
        $table ??= $query->getModel()->getTable();

        if (filled($validated['updated_from'] ?? null)) {
            $query->where($table.'.updated_at', '>=', $validated['updated_from']);
        }

        if (filled($validated['updated_to'] ?? null)) {
            $query->where($table.'.updated_at', '<=', $validated['updated_to']);
        }
    }
}
