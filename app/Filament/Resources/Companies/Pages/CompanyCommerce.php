<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Enums\RcAssetChangeType;
use App\Enums\RcAssetDefinitionStatus;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcBizPlanStatus;
use App\Enums\RcBizPlanTargetSide;
use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use App\Models\Rc\AssetAccount;
use App\Models\Rc\AssetDefinition;
use App\Models\Rc\AssetLedger;
use App\Models\Rc\BizPlan;
use App\Models\Rc\Order;
use App\Services\CompanyRcCommerceService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyCommerce extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = '企业权益与订单';

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('企业信息')
                ->schema([
                    TextEntry::make('name')->label('企业名称'),
                    TextEntry::make('credit_code')->label('统一信用代码'),
                    TextEntry::make('contact_phone')->label('联系电话')->placeholder('-'),
                ])
                ->columns(3),
            Section::make('权益余额')
                ->schema([
                    RepeatableEntry::make('asset_balances')
                        ->hiddenLabel()
                        ->getStateUsing(fn (Company $record): array => AssetAccount::query()
                            ->where('owner_type', RcAssetOwnerType::Company->value)
                            ->where('owner_id', $record->id)
                            ->orderBy('asset_code')
                            ->get()
                            ->all())
                        ->table([
                            TableColumn::make('权益编码'),
                            TableColumn::make('权益名称'),
                            TableColumn::make('可用余额'),
                            TableColumn::make('冻结余额'),
                            TableColumn::make('到期时间'),
                        ])
                        ->schema([
                            TextEntry::make('asset_code'),
                            TextEntry::make('asset_name'),
                            TextEntry::make('balance')->numeric(),
                            TextEntry::make('frozen_balance')->numeric(),
                            TextEntry::make('expired_at')->dateTime('Y-m-d H:i:s')->placeholder('永久'),
                        ])
                        ->contained(false),
                ]),
            Section::make('订单列表（最近 50 条）')
                ->schema([
                    RepeatableEntry::make('orders')
                        ->hiddenLabel()
                        ->getStateUsing(fn (Company $record): array => Order::query()
                            ->where('payer_type', RcAssetOwnerType::Company->value)
                            ->where('payer_id', $record->id)
                            ->latest('id')
                            ->limit(50)
                            ->get()
                            ->all())
                        ->table([
                            TableColumn::make('订单号'),
                            TableColumn::make('商品'),
                            TableColumn::make('数量'),
                            TableColumn::make('实付'),
                            TableColumn::make('状态'),
                            TableColumn::make('创建时间'),
                        ])
                        ->schema([
                            TextEntry::make('order_no'),
                            TextEntry::make('product_name'),
                            TextEntry::make('quantity')->numeric(),
                            TextEntry::make('paid_amount')->money('CNY'),
                            TextEntry::make('order_status')
                                ->badge()
                                ->formatStateUsing(fn (int $state): string => match ($state) {
                                    1 => '已完成',
                                    2 => '已取消',
                                    3 => '已关闭',
                                    default => '待支付',
                                }),
                            TextEntry::make('created_at')->dateTime('Y-m-d H:i:s'),
                        ])
                        ->contained(false),
                ]),
            Section::make('权益流水（最近 100 条）')
                ->schema([
                    RepeatableEntry::make('asset_ledgers')
                        ->hiddenLabel()
                        ->getStateUsing(fn (Company $record): array => AssetLedger::query()
                            ->where('owner_type', RcAssetOwnerType::Company->value)
                            ->where('owner_id', $record->id)
                            ->latest('id')
                            ->limit(100)
                            ->get()
                            ->all())
                        ->table([
                            TableColumn::make('发生时间'),
                            TableColumn::make('权益编码'),
                            TableColumn::make('类型'),
                            TableColumn::make('变动'),
                            TableColumn::make('变动后余额'),
                            TableColumn::make('备注'),
                        ])
                        ->schema([
                            TextEntry::make('happened_at')->dateTime('Y-m-d H:i:s'),
                            TextEntry::make('asset_code'),
                            TextEntry::make('change_type')
                                ->badge()
                                ->formatStateUsing(fn (RcAssetChangeType $state): string => match ($state) {
                                    RcAssetChangeType::Grant => '发放',
                                    RcAssetChangeType::Consume => '消耗',
                                    RcAssetChangeType::Refund => '退款',
                                    RcAssetChangeType::Expire => '过期',
                                    RcAssetChangeType::ManualAdjustment => '人工调整',
                                }),
                            TextEntry::make('delta')->numeric(),
                            TextEntry::make('balance_after')->numeric(),
                            TextEntry::make('remark')->placeholder('-'),
                        ])
                        ->contained(false),
                ]),
        ])->record($this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->increaseAssetAction(),
            $this->addProductAction(),
        ];
    }

    private function increaseAssetAction(): Action
    {
        return Action::make('increaseAsset')
            ->label('增加权益余额')
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->authorize(fn (): bool => CompanyResource::canEdit($this->getRecord()))
            ->schema([
                Select::make('asset_definition_id')
                    ->label('权益')
                    ->options(fn (): array => AssetDefinition::query()
                        ->whereIn('owner_type', [
                            RcAssetOwnerType::Universal->value,
                            RcAssetOwnerType::Company->value,
                        ])
                        ->where('status', RcAssetDefinitionStatus::Enabled->value)
                        ->orderBy('sort')
                        ->get()
                        ->mapWithKeys(fn (AssetDefinition $definition): array => [
                            $definition->id => $definition->asset_name.'（'.$definition->asset_code.'）',
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('quantity')
                    ->label('增加数量')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
                Textarea::make('remark')->label('备注')->required()->maxLength(255),
            ])
            ->action(function (array $data): void {
                CompanyRcCommerceService::make()->increaseAsset(
                    $this->getRecord(),
                    AssetDefinition::query()->findOrFail((int) $data['asset_definition_id']),
                    (int) $data['quantity'],
                    (string) $data['remark'],
                    auth('admin')->id(),
                );

                Notification::make()->title('权益余额已增加')->success()->send();
            });
    }

    private function addProductAction(): Action
    {
        return Action::make('addProduct')
            ->label('增加商品')
            ->icon('heroicon-o-shopping-bag')
            ->color('primary')
            ->authorize(fn (): bool => CompanyResource::canEdit($this->getRecord()))
            ->schema([
                Select::make('biz_plan_id')
                    ->label('RC 商品')
                    ->options(fn (): array => BizPlan::query()
                        ->where('target_side', RcBizPlanTargetSide::Recruiter->value)
                        ->where('status', RcBizPlanStatus::Enabled->value)
                        ->orderBy('sort')
                        ->get()
                        ->mapWithKeys(fn (BizPlan $plan): array => [
                            $plan->id => $plan->plan_name.'（'.$plan->plan_code.'）',
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('quantity')
                    ->label('商品数量')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
                Textarea::make('remark')->label('备注')->required()->maxLength(255),
            ])
            ->action(function (array $data): void {
                CompanyRcCommerceService::make()->addProduct(
                    $this->getRecord(),
                    BizPlan::query()->findOrFail((int) $data['biz_plan_id']),
                    (int) $data['quantity'],
                    (string) $data['remark'],
                    auth('admin')->id(),
                );

                Notification::make()->title('商品已增加，相关权益已发放')->success()->send();
            });
    }
}
