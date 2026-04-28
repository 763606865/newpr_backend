<?php

namespace App\Filament\Resources\LeaveBalances;

use App\Filament\Resources\LeaveBalances\Pages\CreateLeaveBalance;
use App\Filament\Resources\LeaveBalances\Pages\EditLeaveBalance;
use App\Filament\Resources\LeaveBalances\Pages\ListLeaveBalances;
use App\Filament\Resources\LeaveBalances\Schemas\LeaveBalanceForm;
use App\Filament\Resources\LeaveBalances\Tables\LeaveBalancesTable;
use App\Models\Oa\LeaveBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LeaveBalancesResource extends Resource
{
    protected static ?string $model = LeaveBalance::class;

    protected static ?string $label = '假期额度';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?string $recordTitleAttribute = 'year';

    protected static string|UnitEnum|null $navigationGroup = '考勤管理';

    public static function form(Schema $schema): Schema
    {
        return LeaveBalanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveBalancesTable::configure($table);
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
            'index' => ListLeaveBalances::route('/'),
            'create' => CreateLeaveBalance::route('/create'),
            'edit' => EditLeaveBalance::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
