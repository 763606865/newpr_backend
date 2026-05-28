<?php

namespace App\Models\Cast;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AliyunOss implements CastsAttributes, SerializesCastableAttributes
{
    public function __construct(
        private string $disk = 'oss',
        private string $visibility = 'public',
        private int|string $expires = 3600,
    ) {}

    /**
     * Return raw OSS path for model runtime usage (forms/query logic).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): ?string {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $path = parse_url($value, PHP_URL_PATH);

            return is_string($path) ? ltrim($path, '/') : null;
        }

        return ltrim($value, '/');
    }

    /**
     * Persist OSS path to DB. Full URLs are normalized back to relative paths.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): ?string {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);

        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            $path = parse_url($trimmed, PHP_URL_PATH);

            return is_string($path) ? ltrim($path, '/') : null;
        }

        return ltrim($trimmed, '/');
    }

    /**
     * Serialize OSS path to URL for API/array output.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function serialize(
        Model $model,
        string $key,
        mixed $value,
        array $attributes,
    ): ?string {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $path = ltrim($value, '/');

        try {
            if ($this->visibility === 'private') {
                return Storage::disk($this->disk)->temporaryUrl($path, now()->addSeconds((int) $this->expires));
            }

            return Storage::disk($this->disk)->url($path);
        } catch (Throwable) {
            return $path;
        }
    }
}
