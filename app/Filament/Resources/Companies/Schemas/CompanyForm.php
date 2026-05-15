<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Enums\CompanyStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('名称')->required(),
                TextInput::make('credit_code')->label('统一信用代码')->required(),
                TextInput::make('legal_person')->label('企业法人'),
                TextInput::make('contact_phone')->label('联系电话')->required(),
                Textarea::make('address'),
                Select::make('status')
                    ->label('状态')
                    ->options(CompanyStatus::class)
                    ->enum(CompanyStatus::class)
                    ->required(),
            ]);
    }
}
