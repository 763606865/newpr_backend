<?php

namespace App\Filament\Resources\Rc\Reports\Pages;

use App\Enums\RcReportStatus;
use App\Filament\Resources\Rc\Reports\ReportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditReport extends EditRecord
{
    protected static string $resource = ReportResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ((int) ($data['status'] ?? RcReportStatus::Pending->value) !== RcReportStatus::Pending->value) {
            $data['handler_admin_user_id'] ??= auth('admin')->id();
            $data['handled_at'] ??= now();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
