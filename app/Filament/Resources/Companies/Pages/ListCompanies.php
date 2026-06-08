<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Enums\SystemPlanStatus;
use App\Filament\Resources\Companies\CompanyResource;
use App\Jobs\BatchRebindCompanyPlansJob;
use App\Models\Biz\Plan;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            static::batchRebindPlansAction(),
            CreateAction::make(),
        ];
    }

    private static function batchRebindPlansAction(): Action
    {
        return Action::make('batchRebindPlans')
            ->label('批量重绑')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('warning')
            ->authorize(fn (): bool => auth('admin')->user()?->can('Update:Company') ?? false)
            ->modalHeading('批量重绑套餐')
            ->modalDescription('将按所选套餐模板，为所有当前绑定该套餐的企业重新生成权限快照。任务将在后台异步执行。')
            ->modalSubmitActionLabel('提交任务')
            ->schema([
                Select::make('plan_id')
                    ->label('选择套餐')
                    ->options(fn (): array => Plan::query()
                        ->where('status', SystemPlanStatus::Enabled)
                        ->orderBy('sort')
                        ->get()
                        ->mapWithKeys(fn (Plan $plan): array => [
                            $plan->id => sprintf('%s（%s）', $plan->plan_name, $plan->plan_code),
                        ])
                        ->all())
                    ->searchable()
                    ->required(),
            ])
            ->requiresConfirmation()
            ->action(function (array $data): void {
                BatchRebindCompanyPlansJob::dispatch(
                    (int) $data['plan_id'],
                    auth('admin')->id(),
                );

                Notification::make()
                    ->title('批量重绑任务已提交')
                    ->body('任务将在后台执行，完成后可在日志中查看结果。')
                    ->success()
                    ->send();
            });
    }
}
