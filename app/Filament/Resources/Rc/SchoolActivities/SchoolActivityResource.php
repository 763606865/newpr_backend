<?php

namespace App\Filament\Resources\Rc\SchoolActivities;

use App\Filament\Resources\Rc\SchoolActivities\Pages\CreateSchoolActivity;
use App\Filament\Resources\Rc\SchoolActivities\Pages\EditSchoolActivity;
use App\Filament\Resources\Rc\SchoolActivities\Pages\ListSchoolActivities;
use App\Filament\Resources\Rc\SchoolActivities\Schemas\SchoolActivityForm;
use App\Filament\Resources\Rc\SchoolActivities\Tables\SchoolActivitiesTable;
use App\Models\Rc\SchoolActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SchoolActivityResource extends Resource
{
    protected static ?string $model = SchoolActivity::class;

    protected static ?string $label = '校园活动';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'RC招聘';

    public static function form(Schema $schema): Schema
    {
        return SchoolActivityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolActivitiesTable::configure($table);
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
            'index' => ListSchoolActivities::route('/'),
            'create' => CreateSchoolActivity::route('/create'),
            'edit' => EditSchoolActivity::route('/{record}/edit'),
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
