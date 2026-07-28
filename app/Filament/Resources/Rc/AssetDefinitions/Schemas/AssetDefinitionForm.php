<?php

namespace App\Filament\Resources\Rc\AssetDefinitions\Schemas;

use App\Enums\RcAssetDefinitionStatus;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetType;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AssetDefinitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('asset_name')
                    ->label('权益名称')
                    ->required()
                    ->maxLength(100),
                TextInput::make('asset_code')
                    ->label('权益编码')
                    ->required()
                    ->alphaDash()
                    ->maxLength(64)
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit')
                    ->helperText('创建后不可修改，例如 job_urgent。'),
                Select::make('owner_type')
                    ->label('适用主体')
                    ->options(RcAssetOwnerType::class)
                    ->enum(RcAssetOwnerType::class)
                    ->default(RcAssetOwnerType::Universal)
                    ->required(),
                Select::make('asset_type')
                    ->label('权益类型')
                    ->options(RcAssetType::class)
                    ->enum(RcAssetType::class)
                    ->default(RcAssetType::Count)
                    ->required(),
                TextInput::make('consume_scene')
                    ->label('消费场景')
                    ->alphaDash()
                    ->maxLength(64)
                    ->helperText('业务消费动作编码，例如 job_urgent。'),
                TextInput::make('unit')
                    ->label('权益单位')
                    ->required()
                    ->maxLength(20)
                    ->default('次'),
                TextInput::make('default_duration')
                    ->label('默认有效期')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->suffix('天')
                    ->default(0)
                    ->helperText('填写 0 表示永久有效。')
                    ->required(),
                Select::make('status')
                    ->label('状态')
                    ->options(RcAssetDefinitionStatus::class)
                    ->enum(RcAssetDefinitionStatus::class)
                    ->default(RcAssetDefinitionStatus::Enabled)
                    ->required(),
                Textarea::make('description')
                    ->label('权益说明')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->integer()
                    ->default(0)
                    ->required(),
                KeyValue::make('extra')
                    ->label('扩展配置')
                    ->columnSpanFull(),
            ]);
    }
}
