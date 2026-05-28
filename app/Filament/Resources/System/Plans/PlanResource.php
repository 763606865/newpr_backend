<?php

namespace App\Filament\Resources\System\Plans;

use App\Filament\Resources\System\Plans\Pages\CreatePlan;
use App\Filament\Resources\System\Plans\Pages\EditPlan;
use App\Filament\Resources\System\Plans\Pages\ListPlans;
use App\Filament\Resources\System\Plans\Schemas\PlanForm;
use App\Filament\Resources\System\Plans\Tables\PlansTable;
use App\Models\Biz\Plan;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationLabel = '方案管理';

    protected static ?string $modelLabel = '方案';

    protected static ?string $pluralModelLabel = '方案';

    protected static string|UnitEnum|null $navigationGroup = '后台管理';

    public static function form(Schema $schema): Schema
    {
        return PlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlans::route('/'),
            'create' => CreatePlan::route('/create'),
            'edit' => EditPlan::route('/{record}/edit'),
        ];
    }
}
