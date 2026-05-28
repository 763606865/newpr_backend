<?php

namespace App\Filament\Resources\AttendanceSchedules;

use App\Filament\Resources\AttendanceSchedules\Pages\ListAttendanceSchedules;
use App\Filament\Resources\AttendanceSchedules\Schemas\AttendanceScheduleForm;
use App\Filament\Resources\AttendanceSchedules\Tables\AttendanceSchedulesTable;
use App\Models\Oa\AttendanceSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AttendanceSchedulesResource extends Resource
{
    protected static ?string $model = AttendanceSchedule::class;

    protected static ?string $label = '考勤记录';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMinus;

    protected static ?string $recordTitleAttribute = 'date';

    protected static string|UnitEnum|null $navigationGroup = 'OA';

    public static function form(Schema $schema): Schema
    {
        return AttendanceScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceSchedulesTable::configure($table);
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
            'index' => ListAttendanceSchedules::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
