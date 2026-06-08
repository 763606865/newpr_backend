<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Services\CompanyOperationLogService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $companyAttributesBeforeSave = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->after(fn () => CompanyOperationLogService::make()->recordDeleted($this->getRecord())),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->companyAttributesBeforeSave = CompanyOperationLogService::make()
            ->snapshotCompanyAttributes($this->getRecord());
    }

    protected function afterSave(): void
    {
        CompanyOperationLogService::make()->recordCompanyAttributesChanged(
            $this->getRecord(),
            $this->companyAttributesBeforeSave,
        );
    }
}
