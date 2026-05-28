<?php

namespace App\Filament\Resources\Rc\Resumes;

use App\Filament\Resources\Rc\Resumes\Pages\EditResume;
use App\Filament\Resources\Rc\Resumes\Pages\ListResumes;
use App\Filament\Resources\Rc\Resumes\Schemas\ResumeForm;
use App\Filament\Resources\Rc\Resumes\Tables\ResumesTable;
use App\Models\Rc\Resume;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ResumeResource extends Resource
{
    protected static ?string $model = Resume::class;

    protected static ?string $label = '在线简历';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'RC招聘';

    public static function form(Schema $schema): Schema
    {
        return ResumeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResumesTable::configure($table);
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
            'index' => ListResumes::route('/'),
            'edit' => EditResume::route('/{record}/edit'),
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
