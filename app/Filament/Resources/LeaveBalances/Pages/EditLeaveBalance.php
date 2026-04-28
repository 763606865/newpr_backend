<?php

namespace App\Filament\Resources\LeaveBalances\Pages;

use App\Filament\Resources\LeaveBalances\LeaveBalancesResource;
use App\Models\Oa\LeaveType;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditLeaveBalance extends EditRecord
{
    protected static string $resource = LeaveBalancesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
