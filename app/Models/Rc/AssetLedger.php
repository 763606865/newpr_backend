<?php

namespace App\Models\Rc;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Support\Carbon;

/**
 * RC 资产流水 (ledger)
 *
 * @property int $id
 * @property int $account_id
 * @property int $owner_type
 * @property int $owner_id
 * @property string $asset_code
 * @property int $change_type
 * @property int $delta
 * @property int $balance_after
 * @property int $source_type
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
            'owner_type' => 'integer',
            'owner_id' => 'integer',
            'change_type' => 'integer',
            'delta' => 'integer',
            'balance_after' => 'integer',
            'source_type' => 'integer',
            'source_id' => 'integer',
            'happened_at' => 'datetime',
            'extra' => 'array',
        ];
    }
}
