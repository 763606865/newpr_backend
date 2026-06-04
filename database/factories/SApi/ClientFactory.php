<?php

namespace Database\Factories\SApi;

use App\Enums\SApiClientStatus;
use App\Models\SApi\Client;
use App\Services\SApiClientService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $service = SApiClientService::make();

        return [
            'name' => fake()->company().' SApi Client',
            'app_key' => $service->generateAppKey(),
            'app_secret' => $service->generateAppSecret(),
            'status' => SApiClientStatus::Enabled,
            'allowed_ips' => null,
            'remark' => null,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => [
            'status' => SApiClientStatus::Disabled,
        ]);
    }

    /**
     * @param  array<int, string>  $ips
     */
    public function withAllowedIps(array $ips): static
    {
        return $this->state(fn (): array => [
            'allowed_ips' => $ips,
        ]);
    }
}
