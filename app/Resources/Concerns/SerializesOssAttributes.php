<?php

namespace App\Resources\Concerns;

use App\Models\Cast\AliyunOss;
use Illuminate\Database\Eloquent\Model;

trait SerializesOssAttributes
{
    /**
     * @return array{path: ?string, display: ?string}
     */
    protected function ossAttributePair(string $attribute, string $disk = 'oss', string $visibility = 'public', int $expires = 3600): array
    {
        if (! $this->resource instanceof Model) {
            return ['path' => null, 'display' => null];
        }

        $raw = $this->resource->getAttributes()[$attribute] ?? null;

        if (! is_string($raw) || $raw === '') {
            return ['path' => null, 'display' => null];
        }

        $path = ltrim($raw, '/');
        $cast = new AliyunOss($disk, $visibility, $expires);

        return [
            'path' => $path,
            'display' => $cast->toDisplayUrl($path),
        ];
    }
}
