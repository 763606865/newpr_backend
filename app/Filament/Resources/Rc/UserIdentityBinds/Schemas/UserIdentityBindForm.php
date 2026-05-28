<?php

namespace App\Filament\Resources\Rc\UserIdentityBinds\Schemas;

use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserIdentityBindForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')->label('ID')->disabled()->dehydrated(false),
                TextInput::make('user_id')->label('用户ID')->disabled()->dehydrated(false),
                TextInput::make('company_id')->label('企业ID')->disabled()->dehydrated(false),
                Select::make('identity_type')
                    ->label('身份类型')
                    ->options(RcIdentityType::class)
                    ->enum(RcIdentityType::class)
                    ->required(),
                TextInput::make('identity_name')->label('身份名称')->required()->maxLength(100),
                TextInput::make('organization_name')->label('所属机构')->maxLength(120),
                TextInput::make('job_title')->label('岗位头衔')->maxLength(100),
                Select::make('is_default')
                    ->label('默认身份')
                    ->options([
                        0 => '否',
                        1 => '是',
                    ])
                    ->required(),
                Select::make('status')
                    ->label('状态')
                    ->options(RcIdentityStatus::class)
                    ->enum(RcIdentityStatus::class)
                    ->required(),
                KeyValue::make('extra')->label('扩展字段')->columnSpanFull(),
            ]);
    }
}
