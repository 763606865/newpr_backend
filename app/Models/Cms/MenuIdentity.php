<?php

namespace App\Models\Cms;

use App\Enums\CmsMenuAudienceType;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 门户菜单可见身份关联表
 *
 * @property int $id 主键ID
 * @property int $menu_id 菜单ID
 * @property CmsMenuAudienceType $identity_type 可见身份
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read Menu $menu 所属菜单
 */
#[Table('cms_menu_identities')]
#[Fillable([
    'menu_id',
    'identity_type',
])]
#[Visible([
    'id',
    'menu_id',
    'identity_type',
    'created_at',
    'updated_at',
])]
class MenuIdentity extends Model
{
    protected function casts(): array
    {
        return [
            'menu_id' => 'integer',
            'identity_type' => CmsMenuAudienceType::class,
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
}
