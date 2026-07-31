<?php

namespace App\Filament\Resources\ContactInquiries\Schemas;

use App\Enums\RcContactInquiryStatus;
use App\Enums\RcContactProduct;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactInquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('申请信息')
                    ->schema([
                        TextInput::make('name')->label('姓名/称呼')->disabled()->dehydrated(false),
                        TextInput::make('phone')->label('手机号')->disabled()->dehydrated(false),
                        TextInput::make('company_name')->label('公司名称')->disabled()->dehydrated(false),
                        TextInput::make('source')->label('信息来源')->disabled()->dehydrated(false),
                        Select::make('product')
                            ->label('咨询产品')
                            ->options(RcContactProduct::class)
                            ->enum(RcContactProduct::class)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('submitted_at')->label('申请时间')->disabled()->dehydrated(false),
                        Textarea::make('content')
                            ->label('申请内容')
                            ->rows(6)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        TextInput::make('ip')->label('提交IP')->disabled()->dehydrated(false),
                        Textarea::make('user_agent')
                            ->label('User-Agent')
                            ->rows(2)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('跟进信息')
                    ->schema([
                        Select::make('status')
                            ->label('回访状态')
                            ->options(RcContactInquiryStatus::class)
                            ->enum(RcContactInquiryStatus::class)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('followUpAdmin.name')->label('跟进人员')->disabled()->dehydrated(false),
                        TextInput::make('followed_up_at')->label('回访时间')->disabled()->dehydrated(false),
                        Textarea::make('follow_up_note')
                            ->label('跟进备注')
                            ->rows(5)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
