<?php

namespace App\Models\Oa\System;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 系统权限点模型
 *
 * @property int $id
 * @property string $feature_name 权限名称
 * @property string $feature_code 权限唯一编码
 * @property int $menu_id 所属菜单ID
 * @property string|null $description 权限描述
 * @property int $status 0=禁用 1=启用
 */
#[Table('oa_sys_features')]
#[Fillable(['feature_name', 'feature_code', 'menu_id', 'description', 'status'])]
class Feature extends Model
{
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    /**
     * 所属菜单
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    /**
     * 关联的方案
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'oa_sys_plan_features', 'feature_id', 'plan_id');
    }

    /**
     * 是否启用
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }
}
