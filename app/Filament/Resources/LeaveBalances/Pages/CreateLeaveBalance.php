<?php

namespace App\Filament\Resources\LeaveBalances\Pages;

use App\Filament\Resources\LeaveBalances\LeaveBalancesResource;
use App\Models\Oa\LeaveType;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateLeaveBalance extends CreateRecord
{
    protected static string $resource = LeaveBalancesResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['total_days'] = round((float) ($data['total_days'] ?? 0), 2);
        $data['used_days'] = round((float) ($data['used_days'] ?? 0), 2);
        $data['balance_days'] = round($data['total_days'] - $data['used_days'], 2);

        $leaveType = LeaveType::query()->find($data['leave_type_id'] ?? null);

        if ($leaveType && (! $leaveType->allow_negative) && ($data['balance_days'] < 0)) {
            throw ValidationException::withMessages([
                'used_days' => '该假期类型不允许透支，已使用额度不能超过总授予额度。',
            ]);
        }

        return $data;
    }
}
