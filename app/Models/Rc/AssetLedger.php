<?php

namespace App\Models\Rc;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

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
