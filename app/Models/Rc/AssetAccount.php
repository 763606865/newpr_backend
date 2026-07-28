<?php

namespace App\Models\Rc;

use App\Enums\RcAssetOwnerType;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * RC 资产账户
 *
 * @property int $id
 * @property RcAssetOwnerType $owner_type
 * @property int $owner_id
 * @property string $asset_code
 * @property string $asset_name
 * @property int $balance
 * @property int $frozen_balance
 * @property Carbon|null $expired_at
 * @property array|null $extra
 */
#[Table('rc_asset_accounts')]
#[Fillable([
    'owner_type',
    'owner_id',
    'asset_code',
    'asset_name',
    'balance',
    'frozen_balance',
    'expired_at',
    'extra',
])]
class AssetAccount extends Model
{
    protected function casts(): array
    {
        return [
            'owner_type' => RcAssetOwnerType::class,
            'owner_id' => 'integer',
            'balance' => 'integer',
            'frozen_balance' => 'integer',
            'expired_at' => 'datetime',
            'extra' => 'array',
        ];
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(AssetLedger::class, 'account_id');
    }
}
