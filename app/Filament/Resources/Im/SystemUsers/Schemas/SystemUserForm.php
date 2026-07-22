<?php

namespace App\Filament\Resources\Im\SystemUsers\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SystemUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基础信息')
                    ->schema([
                        TextInput::make('code')
                            ->label('系统用户编码')
                            ->required()
                            ->maxLength(64)
                            ->helperText('如 rc_notice、platform_notice。'),
                        TextInput::make('name')
                            ->label('展示名称')
                            ->required()
                            ->maxLength(64),
                        TextInput::make('avatar')
                            ->label('头像')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('启用')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('IM 配置')
                    ->schema([
                        TextInput::make('provider')
                            ->label('IM 服务商')
                            ->required()
                            ->maxLength(64)
                            ->default(fn (): string => (string) config('im.default', 'custom')),
                        TextInput::make('app_code')
                            ->label('应用编码')
                            ->maxLength(255)
                            ->default(fn (): string => (string) config('im.custom.app_code', '')),
                        TextInput::make('external_user_id')
                            ->label('外部用户ID')
                            ->required()
                            ->maxLength(128)
                            ->helperText('业务侧传给 IM 的系统用户 ID。'),
                        TextInput::make('im_user_id')
                            ->label('IM用户ID')
                            ->maxLength(128)
                            ->helperText('IM 平台返回的用户 ID。'),
                    ])
                    ->columns(2),

                Section::make('扩展信息')
                    ->schema([
                        KeyValue::make('extra')
                            ->label('扩展字段')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
