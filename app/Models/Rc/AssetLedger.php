<?php

namespace App\Models\Rc;

use App\Enums\RcAssetChangeType;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetSourceType;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * RC 资产流水 (ledger)
 *
 * @property int $id
 * @property int $account_id
 * @property RcAssetOwnerType $owner_type
 * @property int $owner_id
 * @property string $asset_code
 * @property RcAssetChangeType $change_type
 * @property int $delta
 * @property int $balance_after
 * @property RcAssetSourceType $source_type
 * @property int|null $source_id
 * @property string|null $biz_no
 * @property Carbon|null $happened_at
 * @property array|null $extra
 */
#[Table('rc_asset_ledgers')]
#[Fillable([
    'account_id',
    'owner_type',
    'owner_id',
    'asset_code',
    'change_type',
    'delta',
    'balance_after',
    'source_type',
    'source_id',
    'biz_no',
    'happened_at',
    'remark',
    'extra',
])]
class AssetLedger extends Model
{
    protected function casts(): array
    {
        return [
            'account_id' => 'integer',
            'owner_type' => RcAssetOwnerType::class,
            'owner_id' => 'integer',
            'change_type' => RcAssetChangeType::class,
            'delta' => 'integer',
            'balance_after' => 'integer',
            'source_type' => RcAssetSourceType::class,
            'source_id' => 'integer',
            'happened_at' => 'datetime',
            'extra' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AssetAccount::class, 'account_id');
    }
}
