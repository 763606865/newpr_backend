<?php

namespace App\Models\Rc;

use App\Enums\RcAssetDefinitionStatus;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetType;
use App\Models\Model;
use Database\Factories\Rc\AssetDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * RC 商业化权益定义
 *
 * @property int $id
 * @property string $asset_code
 * @property string $asset_name
 * @property RcAssetOwnerType $owner_type
 * @property RcAssetType $asset_type
 * @property string|null $consume_scene
 * @property string $unit
 * @property int $default_duration
 * @property string|null $description
 * @property RcAssetDefinitionStatus $status
 * @property int $sort
 * @property array<string, mixed>|null $extra
 */
#[Table('rc_asset_definitions')]
#[Fillable([
    'asset_code',
    'asset_name',
    'owner_type',
    'asset_type',
    'consume_scene',
    'unit',
    'default_duration',
    'description',
    'status',
    'sort',
    'extra',
])]
class AssetDefinition extends Model
{
    /** @use HasFactory<AssetDefinitionFactory> */
    use HasFactory;

    protected $attributes = [
        'owner_type' => RcAssetOwnerType::Universal,
        'asset_type' => RcAssetType::Count,
        'unit' => '次',
        'default_duration' => 0,
        'status' => RcAssetDefinitionStatus::Enabled,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'owner_type' => RcAssetOwnerType::class,
            'asset_type' => RcAssetType::class,
            'default_duration' => 'integer',
            'status' => RcAssetDefinitionStatus::class,
            'sort' => 'integer',
            'extra' => 'array',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(AssetAccount::class, 'asset_code', 'asset_code');
    }
}
