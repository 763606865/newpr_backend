<?php

namespace App\Filament\Resources\System\Features\Schemas;

use App\Models\Oa\Menu;
use App\Services\PassportClientService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->label('客户端')
                    ->required()
                    ->options(fn (): array => PassportClientService::make()->options())
                    ->searchable()
                    ->preload(),

                TextInput::make('feature_name')
                    ->label('功能名称')
                    ->required()
                    ->maxLength(50),

                TextInput::make('feature_code')
                    ->label('功能编码')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),

                Select::make('menu_id')
                    ->label('所属菜单')
                    ->options(fn (callable $get): array => Menu::query()
                        ->when(
                            filled($get('client_id')),
                            fn ($query) => $query->where('client_id', (string) $get('client_id'))
                        )
                        ->orderBy('sort')
                        ->orderBy('id')
                        ->pluck('menu_name', 'id')
                        ->toArray())
                    ->required()
                    ->searchable(),

                Textarea::make('description')
                    ->label('功能描述')
                    ->maxLength(255),

                Toggle::make('status')
                    ->label('状态')
                    ->default(true),
            ]);
    }
}
