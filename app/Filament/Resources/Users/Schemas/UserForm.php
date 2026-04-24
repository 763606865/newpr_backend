<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('name')
                    ->label('真实姓名')
                    ->maxLength(50),
                TextInput::make('nickname')
                    ->label('昵称')
                    ->maxLength(50),
                TextInput::make('phone')
                    ->label('手机号')
                    ->tel()
                    ->maxLength(30)
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('email')
                    ->label('邮箱')
                    ->email()
                    ->maxLength(100)
                    ->required(),
                Select::make('gender')
                    ->label('性别')
                    ->options([
                        0 => '未知',
                        1 => '男',
                        2 => '女',
                    ])
                    ->default(0)
                    ->required(),
                Select::make('status')
                    ->label('用户状态')
                    ->options([
                        'active' => '正常',
                        'inactive' => '未激活',
                        'disabled' => '禁用',
                    ])
                    ->default('active')
                    ->required(),
                TextInput::make('password')
                    ->label('密码')
                    ->password()
                    ->revealable()
                    ->minLength(6)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                TextInput::make('last_login_ip')
                    ->label('最后登录IP')
                    ->disabled()
                    ->dehydrated(false),
                DateTimePicker::make('last_login_at')
                    ->label('最后登录时间')
                    ->seconds(false)
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}
