<?php

namespace App\Models\Rc;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

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
            'owner_type' => 'integer',
            'owner_id' => 'integer',
            'balance' => 'integer',
            'frozen_balance' => 'integer',
            'expired_at' => 'datetime',
            'extra' => 'array',
        ];
    }
}
