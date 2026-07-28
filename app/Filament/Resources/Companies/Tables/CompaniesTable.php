<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\CompanyPlanStatus;
use App\Enums\CompanyStatus;
use App\Enums\SystemPlanStatus;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Companies\Schemas\CompanyOperationLogsSchema;
use App\Models\Company;
use App\Models\Oa\Biz\Plan;
use App\Services\CompanyApprovalBenefitService;
use App\Services\CompanyOperationLogService;
use App\Services\RcNotificationService;
use App\Services\SmsService;
use App\Services\SysPlanService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['currentPlans']))
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('name')->label('名称'),
                TextColumn::make('credit_code')->label('统一信用代码'),
                TextColumn::make('contact_phone')->label('联系电话'),
                TextColumn::make('currentPlans.plan_name')
                    ->label('当前套餐')
                    ->placeholder('无'),
                TextColumn::make('currentPlans.pivot.status')
                    ->label('套餐状态')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $status = CompanyPlanStatus::tryFrom((int) $state);

                        return $status?->getLabel() ?? '无';
                    })
                    ->color(function ($state): string {
                        return match (CompanyPlanStatus::tryFrom((int) $state)) {
                            CompanyPlanStatus::Disabled => 'gray',
                            CompanyPlanStatus::Enabled => 'success',
                            CompanyPlanStatus::Pause => 'warning',
                            default => 'gray',
                        };
                    })
                    ->placeholder('无'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(function (mixed $state): string {
                        $status = self::resolveCompanyStatus($state);

                        return $status?->getLabel() ?? '无';
                    })
                    ->color(function (mixed $state): string {
                        return match (self::resolveCompanyStatus($state)) {
                            CompanyStatus::Disabled => 'gray',
                            CompanyStatus::Enabled => 'success',
                            CompanyStatus::Auditing => 'warning',
                            default => 'gray',
                        };
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(CompanyStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                static::actionAudit(),
                Action::make('commerce')
                    ->label('权益与订单')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->authorize(fn (Company $record): bool => CompanyResource::canView($record))
                    ->url(fn (Company $record): string => CompanyResource::getUrl('commerce', ['record' => $record])),
                static::actionBindPlan(),
                static::actionOperationLogs(),
                ActionGroup::make([
                    static::actionRefreshPlan(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->after(fn (Company $record) => CompanyOperationLogService::make()->recordDeleted($record)),
                ])->label('更多'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    private static function resolveCompanyStatus(mixed $state): ?CompanyStatus
    {
        if ($state instanceof CompanyStatus) {
            return $state;
        }

        return CompanyStatus::tryFrom((int) $state);
    }

    private static function actionOperationLogs(): Action
    {
        return Action::make('operationLogs')
            ->label('日志')
            ->icon('heroicon-o-clipboard-document-list')
            ->color('gray')
            ->authorize(fn (Company $record): bool => CompanyResource::canView($record))
            ->modalHeading(fn (Company $record): string => sprintf('操作日志 - %s', $record->name))
            ->modalDescription('查看该企业的运营操作记录。')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('关闭')
            ->modalWidth('5xl')
            ->schema(CompanyOperationLogsSchema::components());
    }

    private static function actionAudit(): Action
    {
        return Action::make('audit')
            ->label('审批')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('warning')
            ->authorize(fn (Company $record): bool => CompanyResource::canEdit($record))
            ->visible(fn (Company $record): bool => self::resolveCompanyStatus($record->status) === CompanyStatus::Auditing)
            ->modalHeading('企业入驻审批')
            ->modalDescription('请确认企业信息后进行审批。')
            ->modalSubmitAction(false)
            ->schema([
                Section::make('企业信息')
                    ->schema([
                        TextEntry::make('name')->label('企业名称'),
                        TextEntry::make('credit_code')->label('统一信用代码'),
                        TextEntry::make('legal_person')->label('企业法人'),
                        TextEntry::make('contact_phone')->label('联系电话'),
                        TextEntry::make('address')->label('企业地址'),
                    ])
                    ->columns(2),
                Section::make('通知设置')
                    ->schema([
                        Checkbox::make('send_sms_notification')
                            ->label('发送短信通知')
                            ->default(true),
                    ]),
            ])
            ->extraModalFooterActions([
                Action::make('approve')
                    ->label('通过')
                    ->color('success')
                    ->authorize(fn (Company $record): bool => CompanyResource::canEdit($record))
                    ->action(function (Company $record, array $data): void {
                        $beforeStatus = self::resolveCompanyStatus($record->status) ?? CompanyStatus::Auditing;
                        $adminId = auth('admin')->id();

                        DB::transaction(function () use ($record, $adminId): void {
                            $record->update([
                                'status' => CompanyStatus::Enabled,
                                'auditor_id' => $adminId,
                            ]);

                            CompanyApprovalBenefitService::make()->grant($record);
                        });

                        $record = $record->fresh();
                        CompanyOperationLogService::make()->recordAuditApproved($record, $beforeStatus);
                        RcNotificationService::make()->notifyCompanyAuditResult($record, true);

                        if ((bool) ($data['send_sms_notification'] ?? true)) {
                            self::sendAuditSmsNotification($record, true);
                        }
                    })
                    ->successNotificationTitle('已通过该企业入驻申请')
                    ->cancelParentActions(),
                Action::make('reject')
                    ->label('拒绝')
                    ->color('danger')
                    ->authorize(fn (Company $record): bool => CompanyResource::canEdit($record))
                    ->action(function (Company $record, array $data): void {
                        $beforeStatus = self::resolveCompanyStatus($record->status) ?? CompanyStatus::Auditing;
                        $adminId = auth('admin')->id();

                        $record->update([
                            'status' => CompanyStatus::Disabled,
                            'auditor_id' => $adminId,
                        ]);

                        $record = $record->fresh();

                        CompanyOperationLogService::make()->recordAuditRejected($record, $beforeStatus);
                        RcNotificationService::make()->notifyCompanyAuditResult($record, false);

                        if ((bool) ($data['send_sms_notification'] ?? true)) {
                            self::sendAuditSmsNotification($record, false);
                        }
                    })
                    ->successNotificationTitle('已拒绝该企业入驻申请')
                    ->cancelParentActions(),
            ]);
    }

    private static function sendAuditSmsNotification(Company $company, bool $approved): void
    {
        $mobile = trim((string) $company->contact_phone);

        if ($mobile === '') {
            Log::warning('company_audit_sms_skipped', [
                'company_id' => $company->id,
                'reason' => 'missing_contact_phone',
            ]);

            return;
        }

        SmsService::make()->send(
            mobile: $mobile,
            templateId: $approved ? '810334' : '810335',
            templateContent: [
                'company_name' => $company->name,
            ],
            signature: '【中测高科人才测评】',
        );
    }

    private static function actionBindPlan(): Action
    {
        return Action::make('bindPlan')
            ->label('绑定套餐')
            ->icon('heroicon-o-rectangle-stack')
            ->color('primary')
            ->authorize(fn (Company $record): bool => CompanyResource::canEdit($record))
            ->modalHeading('绑定套餐')
            ->modalSubmitActionLabel('确认绑定')
            ->fillForm(function (Company $record): array {
                $currentPlan = $record->companyPlans()
                    ->where('is_current', 1)
                    ->with('ship')
                    ->first();

                return [
                    'plan_id' => $currentPlan?->plan_id,
                    'pay_amount' => $currentPlan?->ship?->pay_amount,
                    'remark' => '',
                ];
            })
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
                TextInput::make('pay_amount')
                    ->label('实付金额')
                    ->numeric()
                    ->prefix('¥'),
                Textarea::make('remark')
                    ->label('备注')
                    ->maxLength(255),
            ])
            ->action(function (Company $record, array $data): void {
                $plan = Plan::query()->findOrFail((int) $data['plan_id']);
                $logService = CompanyOperationLogService::make();
                $beforePlan = $logService->snapshotCurrentPlan($record);

                $ship = [];

                if (filled($data['pay_amount'] ?? null)) {
                    $ship['pay_amount'] = (float) $data['pay_amount'];
                }

                if (filled($data['remark'] ?? null)) {
                    $ship['remark'] = (string) $data['remark'];
                }

                SysPlanService::make()->resolve($record, $plan, $ship);

                $afterPlan = $logService->snapshotCurrentPlan($record->fresh());

                if (is_array($afterPlan)) {
                    $logService->recordPlanBound($record->fresh(), $beforePlan, $afterPlan, $ship);
                }
            })
            ->successNotificationTitle('套餐绑定成功');
    }

    private static function actionRefreshPlan(): Action
    {
        return Action::make('refreshPlan')
            ->label('刷新套餐')
            ->icon('heroicon-o-arrow-path')
            ->color('info')
            ->authorize(fn (Company $record): bool => CompanyResource::canEdit($record))
            ->visible(fn (Company $record): bool => $record->companyPlans()
                ->where('is_current', 1)
                ->where('status', CompanyPlanStatus::Enabled)
                ->exists())
            ->requiresConfirmation()
            ->modalHeading('刷新套餐')
            ->modalDescription(fn (Company $record): string => sprintf(
                '将按当前套餐模板重新生成权限快照，并新增一条 ship 记录。企业：%s',
                $record->name,
            ))
            ->modalSubmitActionLabel('确认刷新')
            ->action(function (Company $record): void {
                $logService = CompanyOperationLogService::make();
                $beforePlan = $logService->snapshotCurrentPlan($record);
                $remark = '刷新套餐：'.now()->toDateTimeString();

                SysPlanService::make()->refreshCurrentPlan($record, $remark);

                $afterPlan = $logService->snapshotCurrentPlan($record->fresh());

                if (is_array($afterPlan)) {
                    $logService->recordPlanRefreshed($record->fresh(), $beforePlan, $afterPlan, $remark);
                }
            })
            ->successNotificationTitle('套餐已刷新');
    }
}
