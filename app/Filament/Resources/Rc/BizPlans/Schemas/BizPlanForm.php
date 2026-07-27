<?php

namespace App\Filament\Resources\Rc\BizPlans\Schemas;

use App\Enums\RcAssetDefinitionStatus;
use App\Enums\RcBizPlanBillingCycle;
use App\Enums\RcBizPlanProductType;
use App\Enums\RcBizPlanStatus;
use App\Enums\RcBizPlanTargetSide;
use App\Models\Rc\AssetDefinition;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BizPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('plan_name')
                    ->label('商品名称')
                    ->required()
                    ->maxLength(50),
                TextInput::make('plan_code')
                    ->label('商品编码')
                    ->required()
                    ->alphaDash()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->helperText('建议使用稳定的英文编码，例如 job_urgent_7d。'),
                Select::make('target_side')
                    ->label('目标用户')
                    ->options(RcBizPlanTargetSide::class)
                    ->enum(RcBizPlanTargetSide::class)
                    ->default(RcBizPlanTargetSide::Recruiter)
                    ->required(),
                Select::make('product_type')
                    ->label('商品类型')
                    ->options(RcBizPlanProductType::class)
                    ->enum(RcBizPlanProductType::class)
                    ->default(RcBizPlanProductType::JobPosting)
                    ->required(),
                TextInput::make('price')
                    ->label('销售价格')
                    ->prefix('¥')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->default(0)
                    ->required(),
                Select::make('billing_cycle')
                    ->label('计费周期')
                    ->options(RcBizPlanBillingCycle::class)
                    ->enum(RcBizPlanBillingCycle::class)
                    ->default(RcBizPlanBillingCycle::OneTime)
                    ->required(),
                TextInput::make('duration')
                    ->label('商品有效期')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->suffix('天')
                    ->default(0)
                    ->helperText('填写 0 表示永久有效。')
                    ->required(),
                Select::make('status')
                    ->label('状态')
                    ->options(RcBizPlanStatus::class)
                    ->enum(RcBizPlanStatus::class)
                    ->default(RcBizPlanStatus::Enabled)
                    ->required(),
                Repeater::make('quota_rules')
                    ->label('权益规则')
                    ->schema([
                        Select::make('asset_code')
                            ->label('权益')
                            ->options(function (Get $get): array {
                                $selectedCode = $get('asset_code');
                                $selectedName = $get('asset_name');

                                $options = AssetDefinition::query()
                                    ->where(function ($query) use ($selectedCode): void {
                                        $query->where('status', RcAssetDefinitionStatus::Enabled);

                                        if (filled($selectedCode)) {
                                            $query->orWhere('asset_code', $selectedCode);
                                        }
                                    })
                                    ->orderBy('sort')
                                    ->orderBy('id')
                                    ->get()
                                    ->mapWithKeys(static fn (AssetDefinition $definition): array => [
                                        $definition->asset_code => $definition->asset_name.'（'.$definition->asset_code.'）',
                                    ])
                                    ->all();

                                if (filled($selectedCode) && ! array_key_exists($selectedCode, $options)) {
                                    $options[$selectedCode] = (filled($selectedName) ? $selectedName : $selectedCode).'（历史权益）';
                                }

                                return $options;
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                $definition = AssetDefinition::query()
                                    ->where('asset_code', $state)
                                    ->first();

                                $set('asset_name', $definition?->asset_name);
                                $set('duration_days', $definition?->default_duration ?? 0);
                            })
                            ->required()
                            ->helperText('权益需先在“权益配置”中创建。'),
                        TextInput::make('asset_name')
                            ->label('权益名称')
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->maxLength(100),
                        TextInput::make('quantity')
                            ->label('发放数量')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                        TextInput::make('duration_days')
                            ->label('权益有效期')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->suffix('天')
                            ->default(0)
                            ->helperText('填写 0 表示跟随商品有效期。')
                            ->required(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel('添加权益')
                    ->reorderable(false)
                    ->columnSpanFull(),
                Textarea::make('remark')
                    ->label('商品说明')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->integer()
                    ->default(0)
                    ->required(),
            ]);
    }
}
