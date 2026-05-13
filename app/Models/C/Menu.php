<?php

namespace App\Models\C;

use App\Enums\SystemMenuType;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 系统菜单模型
 *
 * @property int $id
 * @property int $parent_id 父菜单ID 0=顶级
 * @property string $menu_name 菜单名称
 * @property string|null $menu_code 菜单唯一标识
 * @property int $menu_type 1=菜单 2=按钮/权限点
 * @property string|null $path 路由路径
 * @property string|null $component 前端组件路径
 * @property string|null $icon 菜单图标
 * @property int $sort 显示排序
 * @property int $visible 0=隐藏 1=显示
 * @property string|null $style 样式扩展属性
 * @property string|null $extra 其他扩展属性
 */
#[Table('c_menus')]
#[Fillable(['parent_id', 'menu_name', 'menu_code', 'menu_type', 'path', 'component', 'icon', 'sort', 'visible', 'style', 'extra'])]
class Menu extends Model
{
    protected function casts(): array
    {
        return [
            'menu_type' => SystemMenuType::class,
            'visible' => 'boolean',
            'style' => 'array',
            'extra' => 'array',
        ];
    }

    /**
     * 父菜单
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 子菜单
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * 关联的功能点
     */
    public function features(): HasMany
    {
        return $this->hasMany(Feature::class, 'menu_id');
    }

    /**
     * 是否为顶级菜单
     */
    public function isRoot(): bool
    {
        return $this->parent_id === 0;
    }

    /**
     * 是否为菜单类型
     */
    public function isMenu(): bool
    {
        return $this->menu_type === 1;
    }

    /**
     * 是否为按钮/权限点类型
     */
    public function isButton(): bool
    {
        return $this->menu_type === 2;
    }
}
