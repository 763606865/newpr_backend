<?php

namespace App\Console\Commands\SApi;

use App\Enums\SApiClientStatus;
use App\Models\SApi\Client;
use App\Services\SApiClientService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sapi:client:create
    {name : 客户端名称}
    {--remark= : 备注}
    {--ip=* : 允许访问的 IP，可多次指定（如 --ip=1.2.3.4 --ip=5.6.7.8）}
    {--disabled : 创建为停用状态}')]
#[Description('创建 SApi 接入客户端并生成 app_key / app_secret')]
class CreateClientCommand extends Command
{
    public function handle(SApiClientService $service): int
    {
        $name = trim((string) $this->argument('name'));

        if ($name === '') {
            $this->error('客户端名称不能为空。');

            return self::FAILURE;
        }

        $allowedIps = $this->resolveAllowedIps();

        if ($allowedIps === false) {
            return self::FAILURE;
        }

        $client = $service->create([
            'name' => $name,
            'status' => $this->option('disabled') ? SApiClientStatus::Disabled : SApiClientStatus::Enabled,
            'allowed_ips' => $allowedIps === [] ? null : $allowedIps,
            'remark' => $this->option('remark') ?: null,
        ]);

        $this->displayClient($client);

        $this->warn('请立即保存 App Secret，关闭终端后将无法再次查看明文。');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>|false
     */
    private function resolveAllowedIps(): array|false
    {
        /** @var array<int, string> $rawIps */
        $rawIps = $this->option('ip');

        if ($rawIps === []) {
            return [];
        }

        $ips = [];

        foreach ($rawIps as $raw) {
            foreach (array_map(trim(...), explode(',', (string) $raw)) as $ip) {
                if ($ip === '') {
                    continue;
                }

                if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                    $this->error("IP 格式无效：{$ip}");

                    return false;
                }

                $ips[] = $ip;
            }
        }

        return array_values(array_unique($ips));
    }

    private function displayClient(Client $client): void
    {
        $statusLabel = $client->status === SApiClientStatus::Enabled ? '启用' : '停用';
        $allowedIps = $client->allowed_ips === null || $client->allowed_ips === []
            ? '不限制'
            : implode(', ', $client->allowed_ips);

        $this->newLine();
        $this->info('SApi 客户端已创建。');
        $this->table(
            ['字段', '值'],
            [
                ['ID', (string) $client->id],
                ['名称', $client->name],
                ['App Key', $client->app_key],
                ['App Secret', $client->app_secret],
                ['状态', $statusLabel],
                ['IP 白名单', $allowedIps],
                ['备注', $client->remark ?? '—'],
            ],
        );
    }
}
