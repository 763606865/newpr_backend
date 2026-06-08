<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum CompanyOperationAction: string implements HasLabel
{
    case Created = 'company.created';
    case Updated = 'company.updated';
    case Deleted = 'company.deleted';
    case StatusChanged = 'company.status.changed';
    case AuditApproved = 'company.audit.approved';
    case AuditRejected = 'company.audit.rejected';
    case PlanBound = 'company.plan.bound';
    case PlanRefreshed = 'company.plan.refreshed';
    case PlanBatchRebound = 'company.plan.batch_rebound';

    public function getLabel(): string|null|Htmlable
    {
        return match ($this) {
            self::Created => '创建企业',
            self::Updated => '更新企业信息',
            self::Deleted => '删除企业',
            self::StatusChanged => '变更企业状态',
            self::AuditApproved => '审批通过',
            self::AuditRejected => '审批拒绝',
            self::PlanBound => '绑定套餐',
            self::PlanRefreshed => '刷新套餐',
            self::PlanBatchRebound => '批量重绑套餐',
        };
    }
}
