<?php

namespace App\Filament\Resources\Rc\UserIdentityBinds\Pages;

use App\Enums\RcIdentityType;
use App\Filament\Resources\Rc\UserIdentityBinds\UserIdentityBindResource;
use App\Filament\Resources\Rc\Widgets\RcResourceStats;
use App\Models\Rc\UserIdentityBind;
use Filament\Resources\Pages\ListRecords;

class ListUserIdentityBinds extends ListRecords
{
    protected static string $resource = UserIdentityBindResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RcResourceStats::make([
                'modelClass' => UserIdentityBind::class,
                'todayColumn' => 'created_at',
                'todayLabel' => '今日新增绑定',
                'statusCards' => [
                    ['label' => '求职者身份数', 'value' => RcIdentityType::JobSeeker->value, 'color' => 'info', 'column' => 'identity_type'],
                    ['label' => '招聘方身份数', 'value' => RcIdentityType::Recruiter->value, 'color' => 'warning', 'column' => 'identity_type'],
                ],
            ]),
        ];
    }
}
