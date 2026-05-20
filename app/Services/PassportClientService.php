<?php

namespace App\Services;

use Laravel\Passport\Client as PassportClient;

class PassportClientService extends Service
{
    /**
     * @var array<string, string>|null
     */
    private ?array $options = null;

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $this->options = PassportClient::query()
            ->orderBy('name')
            ->get(['id', 'name', 'provider'])
            ->mapWithKeys(fn (PassportClient $client): array => [
                (string) $client->id => sprintf('%s (%s)', $client->name ?: (string) $client->id, (string) ($client->provider ?? 'unknown')),
            ])
            ->toArray();

        return $this->options;
    }

    public function label(?string $clientId): string
    {
        if ($clientId === null) {
            return '';
        }

        return $this->options()[$clientId] ?? $clientId;
    }
}
