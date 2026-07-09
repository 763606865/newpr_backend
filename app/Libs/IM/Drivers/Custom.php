<?php

namespace App\Libs\IM\Drivers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class Custom extends AbstractDriver
{
    protected function baseUrl(): string
    {
        return rtrim($this->config['end_point'] ?? '', '\/');
    }

    protected function appCode(): string
    {
        return $this->config['app_code'] ?? ($this->config['app_id'] ?? '');
    }

    public function ping(): bool
    {
        $base = $this->baseUrl();
        if ($base === '') {
            return false;
        }

        try {
            $resp = Http::withHeaders(['Accept' => 'application/json'])
                ->timeout(3)
                ->get($base.'/health');

            return $resp->successful();
        } catch (ConnectionException $e) {
            return false;
        }
    }

    public function sendMessage(string $from, string $to, string $message, array $options = []): array
    {
        $base = $this->baseUrl();
        $app = $this->appCode();
        $url = $base.'/admin/apps/'.$app.'/messages';

        $payload = array_merge(['from' => $from, 'to' => $to, 'message' => $message], $options);

        $resp = Http::withHeaders(['Accept' => 'application/json'])
            ->timeout(5)
            ->post($url, $payload);

        if (! $resp->successful()) {
            return ['success' => false, 'status' => $resp->status(), 'body' => $resp->body()];
        }

        return $resp->json();
    }

    public function createOrUpdateUser(array $payload): array
    {
        $base = $this->baseUrl();
        $app = $this->appCode();
        $url = $base.'/admin/apps/'.$app.'/users';

        $resp = Http::withHeaders(['Accept' => 'application/json'])
            ->timeout(5)
            ->post($url, $payload);

        if (! $resp->successful()) {
            return ['success' => false, 'status' => $resp->status(), 'body' => $resp->body()];
        }

        return $resp->json();
    }

    public function listUsers(int $limit = 50): array
    {
        $base = $this->baseUrl();
        $app = $this->appCode();
        $limit = max(1, min(200, $limit));

        $url = $base.'/admin/apps/'.$app.'/users';

        $resp = Http::withHeaders(['Accept' => 'application/json'])
            ->timeout(5)
            ->get($url, ['limit' => $limit]);

        if (! $resp->successful()) {
            return ['success' => false, 'status' => $resp->status(), 'body' => $resp->body()];
        }

        return $resp->json();
    }

    public function getUser(string $externalUserId): array
    {
        $base = $this->baseUrl();
        $app = $this->appCode();

        $url = $base.'/admin/apps/'.$app.'/users/'.rawurlencode($externalUserId);

        $resp = Http::withHeaders(['Accept' => 'application/json'])
            ->timeout(5)
            ->get($url);

        if (! $resp->successful()) {
            return ['success' => false, 'status' => $resp->status(), 'body' => $resp->body()];
        }

        return $resp->json();
    }

    public function updateUserStatus(string $externalUserId, string $status): array
    {
        $allowed = ['active', 'disabled'];
        if (! in_array($status, $allowed, true)) {
            return ['success' => false, 'message' => 'user status invalid'];
        }

        $base = $this->baseUrl();
        $app = $this->appCode();

        $url = $base.'/admin/apps/'.$app.'/users/'.rawurlencode($externalUserId).'/status';

        $resp = Http::withHeaders(['Accept' => 'application/json'])
            ->timeout(5)
            ->patch($url, ['status' => $status]);

        if (! $resp->successful()) {
            return ['success' => false, 'status' => $resp->status(), 'body' => $resp->body()];
        }

        return $resp->json();
    }
}
