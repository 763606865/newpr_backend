<?php

namespace App\Models;

use App\Enums\CompanyOperationAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * 企业运营操作日志
 *
 * @property int $id
 * @property int $company_id 企业ID
 * @property int|null $operator_id 操作人ID（多态，null 表示系统行为）
 * @property string|null $operator_type 操作人类型（多态）
 * @property CompanyOperationAction $action 操作类型编码
 * @property string|null $summary 操作摘要
 * @property array{before?: array<string, mixed>, after?: array<string, mixed>}|null $changes 变更详情
 * @property string|null $ip 操作IP
 * @property string|null $user_agent User-Agent
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property-read Company $company 所属企业
 * @property-read AdminUser|BUser|null $operator 操作人（运营管理员 / B端用户 / null=系统）
 */
#[Table('company_operation_logs')]
#[Fillable([
    'company_id',
    'operator_id',
    'operator_type',
    'action',
    'summary',
    'changes',
    'ip',
    'user_agent',
    'extra',
    'created_at',
])]
class CompanyOperationLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'operator_id' => 'integer',
            'action' => CompanyOperationAction::class,
            'changes' => 'array',
            'extra' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * 所属企业
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * 操作人（运营管理员 / B端用户；null 表示系统行为）
     */
    public function operator(): MorphTo
    {
        return $this->morphTo();
    }

    public function isSystemAction(): bool
    {
        return blank($this->operator_id) || blank($this->operator_type);
    }
}
