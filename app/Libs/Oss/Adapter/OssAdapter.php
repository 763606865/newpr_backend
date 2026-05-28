<?php

namespace App\Libs\Oss\Adapter;

use DateTimeInterface;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use OSS\OssClient;

class OssAdapter implements FilesystemAdapter
{
    protected OssClient $client;

    protected string $bucket;

    public function __construct(OssClient $client, $bucket)
    {
        $this->client = $client;
        $this->bucket = $bucket;
    }

    public function fileExists(string $path): bool
    {
        try {
            return $this->withoutSdkDeprecationWarnings(fn (): bool => $this->client->doesObjectExist($this->bucket, $path));
        } catch (\Exception $e) {
            return false;
        }
    }

    public function directoryExists(string $path): bool
    {
        return true; // OSS doesn't have explicit directories
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->withoutSdkDeprecationWarnings(fn () => $this->client->putObject($this->bucket, $path, $contents));
        } catch (\Exception $e) {
            throw new \RuntimeException("OSS write failed: {$e->getMessage()}");
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
    }

    public function read(string $path): string
    {
        try {
            return $this->withoutSdkDeprecationWarnings(fn (): string => $this->client->getObject($this->bucket, $path));
        } catch (\Exception $e) {
            throw new \RuntimeException("OSS read failed: {$e->getMessage()}");
        }
    }

    public function readStream(string $path)
    {
        $content = $this->read($path);
        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $this->withoutSdkDeprecationWarnings(fn () => $this->client->deleteObject($this->bucket, $path));
        } catch (\Exception $e) {
            throw new \RuntimeException("OSS delete failed: {$e->getMessage()}");
        }
    }

    public function deleteDirectory(string $path): void
    {
        // OSS doesn't have explicit directories
    }

    public function createDirectory(string $path, Config $config): void
    {
        // OSS creates directories implicitly
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return [];
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->withoutSdkDeprecationWarnings(function () use ($source, $destination): void {
                $this->client->copyObject($this->bucket, $source, $this->bucket, $destination);
                $this->client->deleteObject($this->bucket, $source);
            });
        } catch (\Exception $e) {
            throw new \RuntimeException("OSS move failed: {$e->getMessage()}");
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->withoutSdkDeprecationWarnings(fn () => $this->client->copyObject($this->bucket, $source, $this->bucket, $destination));
        } catch (\Exception $e) {
            throw new \RuntimeException("OSS copy failed: {$e->getMessage()}");
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            $metadata = $this->withoutSdkDeprecationWarnings(fn (): array => $this->client->getObjectMeta($this->bucket, $path));
            $lastModified = $this->metadataValue($metadata, 'last-modified', 'Last-Modified');
            $timestamp = strtotime(is_string($lastModified) ? $lastModified : 'now');

            return new FileAttributes($path, lastModified: $timestamp);
        } catch (\Exception $e) {
            return new FileAttributes($path, lastModified: time());
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            $metadata = $this->withoutSdkDeprecationWarnings(fn (): array => $this->client->getObjectMeta($this->bucket, $path));
            $size = (int) ($this->metadataValue($metadata, 'content-length', 'Content-Length') ?? 0);

            return new FileAttributes($path, $size);
        } catch (\Exception $e) {
            return new FileAttributes($path, 0);
        }
    }

    public function mimeType(string $path): FileAttributes
    {
        try {
            $metadata = $this->withoutSdkDeprecationWarnings(fn (): array => $this->client->getObjectMeta($this->bucket, $path));
            $mimeType = (string) ($this->metadataValue($metadata, 'content-type', 'Content-Type') ?? 'application/octet-stream');

            return new FileAttributes($path, mimeType: $mimeType);
        } catch (\Exception $e) {
            return new FileAttributes($path, mimeType: 'application/octet-stream');
        }
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, visibility: 'public'); // OSS objects are typically public
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // OSS visibility is usually set at bucket level
    }

    /**
     * Get the public URL for a file.
     */
    public function getUrl(string $path): string
    {
        // Use the endpoint and bucket to construct the URL
        $endpoint = config('filesystems.disks.oss.endpoint', 'oss-cn-hangzhou.aliyuncs.com');
        // Remove protocol if present
        $endpoint = preg_replace('|^https?://|', '', $endpoint);

        return "https://{$this->bucket}.{$endpoint}/".ltrim($path, '/');
    }

    /**
     * Get a temporary signed URL for private OSS objects.
     */
    public function getTemporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        $timeout = max(1, $expiration->getTimestamp() - time());

        return $this->withoutSdkDeprecationWarnings(
            fn (): string => $this->client->signUrl($this->bucket, ltrim($path, '/'), $timeout, OssClient::OSS_HTTP_GET, $options)
        );
    }

    /**
     * Execute OSS SDK calls without surfacing deprecated notices from vendor SDK internals.
     *
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    private function withoutSdkDeprecationWarnings(callable $callback): mixed
    {
        set_error_handler(static fn (int $severity, string $message, string $file): bool => $severity === E_DEPRECATED && str_contains($file, 'aliyuncs/oss-sdk-php'));

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }

    private function metadataValue(array $metadata, string ...$keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $metadata)) {
                return $metadata[$key];
            }
        }

        $lowerCaseMetadata = array_change_key_case($metadata, CASE_LOWER);

        foreach ($keys as $key) {
            $lowerKey = strtolower($key);

            if (array_key_exists($lowerKey, $lowerCaseMetadata)) {
                return $lowerCaseMetadata[$lowerKey];
            }
        }

        return null;
    }
}
