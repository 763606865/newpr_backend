<?php

namespace App\Filament\Resources\Rc\Offers\Schemas;

use App\Enums\RcOfferStatus;
use App\Enums\RcSalaryUnit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')->label('ID')->disabled()->dehydrated(false),
                TextInput::make('company_id')->label('企业ID')->disabled()->dehydrated(false),
                TextInput::make('application_id')->label('投递ID')->disabled()->dehydrated(false),
                TextInput::make('offer_no')->label('Offer编号')->disabled()->dehydrated(false),
                TextInput::make('salary_min')->label('最低薪资')->numeric(),
                TextInput::make('salary_max')->label('最高薪资')->numeric(),
                Select::make('salary_unit')
                    ->label('薪资单位')
                    ->options(RcSalaryUnit::class)
                    ->enum(RcSalaryUnit::class)
                    ->required(),
                DatePicker::make('entry_date')->label('入职日期'),
                DatePicker::make('expire_date')->label('Offer过期日期'),
                Select::make('status')
                    ->label('Offer状态')
                    ->options(RcOfferStatus::class)
                    ->enum(RcOfferStatus::class)
                    ->required(),
                DateTimePicker::make('sent_at')->label('发送时间'),
                DateTimePicker::make('replied_at')->label('回复时间'),
                Textarea::make('note')->label('备注')->rows(3)->columnSpanFull(),
                KeyValue::make('extra')->label('扩展字段')->columnSpanFull(),
            ]);
    }
}
