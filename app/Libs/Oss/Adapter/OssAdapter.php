<?php

namespace App\Libs\Oss\Adapter;

use League\Flysystem\Config;
use League\Flysystem\DecoratedAdapter;
use OSS\OssClient;

class OssAdapter extends DecoratedAdapter
{
    protected OssClient $client;
    protected string $bucket;

    public function __construct(OssClient $client, $bucket)
    {
        parent::__construct($this);
        $this->client = $client;
        $this->bucket = $bucket;
    }

    public function write($path, $contents, Config $config): void
    {
        $this->client->putObject($this->bucket, $path, $contents);
    }

    public function read($path): string
    {
        return $this->client->getObject($this->bucket, $path);
    }

    public function delete($path): void
    {
        $this->client->deleteObject($this->bucket, $path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return [];
    }

    public function getMetadata($path): ?array
    {
        return $this->client->getObjectMeta($this->bucket, $path);
    }

    public function getSize($path): array
    {
        $meta = $this->getMetadata($path);
        return ['size' => $meta['content-length'] ?? 0];
    }

    public function getMimetype($path): array
    {
        $meta = $this->getMetadata($path);
        return ['mimetype' => $meta['content-type'] ?? 'application/octet-stream'];
    }

    public function getTimestamp($path): array
    {
        $meta = $this->getMetadata($path);
        return ['timestamp' => strtotime($meta['last-modified'] ?? '')];
    }

    public function update($path, $contents, Config $config): void
    {
        $this->write($path, $contents, $config);
    }

    public function rename($path, $newpath): void
    {
        $this->client->copyObject($this->bucket, $path, $this->bucket, $newpath);
        $this->client->deleteObject($this->bucket, $path);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->client->copyObject($this->bucket, $source, $this->bucket, $destination, $config->toArray());
    }

    public function has(string $path): ?bool
    {
        return $this->client->doesObjectExist($this->bucket, $path);
    }
}
