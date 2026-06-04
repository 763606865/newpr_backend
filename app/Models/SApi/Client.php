<?php

namespace App\Models\SApi;

use App\Enums\SApiClientStatus;
use App\Models\Model;
use Database\Factories\SApi\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * SApi 接入客户端
 *
 * @property int $id
 * @property string $name
 * @property string $app_key
 * @property string $app_secret
 * @property SApiClientStatus $status
 * @property array<int, string>|null $allowed_ips
 * @property string|null $remark
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('sapi_clients')]
#[Fillable([
    'name',
    'app_key',
    'app_secret',
    'status',
    'allowed_ips',
    'remark',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'status' => SApiClientStatus::Enabled,
    ];

    protected function casts(): array
    {
        return [
            'status' => SApiClientStatus::class,
            'app_secret' => 'encrypted',
            'allowed_ips' => 'array',
        ];
    }

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', SApiClientStatus::Enabled);
    }

    public function isIpAllowed(?string $ip): bool
    {
        if ($ip === null || $ip === '') {
            return false;
        }

        $allowedIps = $this->allowed_ips;

        if ($allowedIps === null || $allowedIps === []) {
            return true;
        }

        return in_array($ip, $allowedIps, true);
    }
}
