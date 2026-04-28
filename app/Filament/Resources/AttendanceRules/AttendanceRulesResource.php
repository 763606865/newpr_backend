<?php

namespace App\Filament\Resources\AttendanceRules;

use App\Filament\Resources\AttendanceRules\Pages\CreateAttendanceRule;
use App\Filament\Resources\AttendanceRules\Pages\EditAttendanceRule;
use App\Filament\Resources\AttendanceRules\Pages\ListAttendanceRules;
use App\Filament\Resources\AttendanceRules\Schemas\AttendanceRuleForm;
use App\Filament\Resources\AttendanceRules\Tables\AttendanceRulesTable;
use App\Models\Oa\AttendanceRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AttendanceRulesResource extends Resource
{
    protected static ?string $model = AttendanceRule::class;

    protected static ?string $label = '考勤规则';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = '考勤管理';

    public static function form(Schema $schema): Schema
    {
        return AttendanceRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceRulesTable::configure($table);
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
            'index' => ListAttendanceRules::route('/'),
            'create' => CreateAttendanceRule::route('/create'),
            'edit' => EditAttendanceRule::route('/{record}/edit'),
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
