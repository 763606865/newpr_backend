<?php

namespace App\Filament\Resources\Rc\Interviews;

use App\Filament\Resources\Rc\Interviews\Pages\EditInterview;
use App\Filament\Resources\Rc\Interviews\Pages\ListInterviews;
use App\Filament\Resources\Rc\Interviews\Schemas\InterviewForm;
use App\Filament\Resources\Rc\Interviews\Tables\InterviewsTable;
use App\Models\Rc\Interview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class InterviewResource extends Resource
{
    protected static ?string $model = Interview::class;

    protected static ?string $label = '面试记录';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'RC招聘';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return InterviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InterviewsTable::configure($table);
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
            'index' => ListInterviews::route('/'),
            'edit' => EditInterview::route('/{record}/edit'),
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
