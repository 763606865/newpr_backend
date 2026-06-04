<?php

namespace App\Services;

use App\Enums\SApiClientStatus;
use App\Models\SApi\Client;
use Illuminate\Database\QueryException;
use RuntimeException;

class SApiClientService extends Service
{
    public function generateAppKey(): string
    {
        return 'sapi_'.bin2hex(random_bytes(16));
    }

    public function generateAppSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * @param  array{
     *     name: string,
     *     status?: SApiClientStatus,
     *     allowed_ips?: array<int, string>|null,
     *     remark?: string|null,
     * }  $attributes
     */
    public function create(array $attributes): Client
    {
        $plainSecret = $this->generateAppSecret();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $client = new Client;
            $client->fill([
                'name' => $attributes['name'],
                'app_key' => $this->generateAppKey(),
                'app_secret' => $plainSecret,
                'status' => $attributes['status'] ?? SApiClientStatus::Enabled,
                'allowed_ips' => $attributes['allowed_ips'] ?? null,
                'remark' => $attributes['remark'] ?? null,
            ]);

            try {
                $client->save();

                return $client;
            } catch (QueryException $exception) {
                if (! $this->isDuplicateAppKeyException($exception)) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('无法生成唯一的 app_key，请重试。');
    }

    private function isDuplicateAppKeyException(QueryException $exception): bool
    {
        $errorCode = (int) ($exception->errorInfo[1] ?? 0);

        return in_array($errorCode, [1062, 19], true);
    }
}
