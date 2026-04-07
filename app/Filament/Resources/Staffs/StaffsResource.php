<?php

namespace App\Filament\Resources\Staffs;

use App\Filament\Resources\Staffs\Pages\CreateStaffs;
use App\Filament\Resources\Staffs\Pages\EditStaffs;
use App\Filament\Resources\Staffs\Pages\ListStaffs;
use App\Filament\Resources\Staffs\Schemas\StaffsForm;
use App\Filament\Resources\Staffs\Tables\StaffsTable;
use App\Models\Staff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class StaffsResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $label = '职工管理';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?string $recordTitleAttribute = 'staff';

    protected static string | UnitEnum | null $navigationGroup = '组织架构';

    public static function form(Schema $schema): Schema
    {
        return StaffsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffsTable::configure($table);
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
            'index' => ListStaffs::route('/'),
            'create' => CreateStaffs::route('/create'),
            'edit' => EditStaffs::route('/{record}/edit'),
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
