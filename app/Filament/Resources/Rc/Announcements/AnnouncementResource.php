<?php

namespace App\Filament\Resources\Rc\Announcements;

use App\Filament\Resources\Rc\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Rc\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Rc\Announcements\Pages\ListAnnouncements;
use App\Filament\Resources\Rc\Announcements\Schemas\AnnouncementForm;
use App\Filament\Resources\Rc\Announcements\Tables\AnnouncementsTable;
use App\Models\Rc\Announcement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $label = '招聘公告';

    protected static ?string $navigationLabel = '招聘公告';

    protected static ?string $slug = 'rc/announcements';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'RC招聘';

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return AnnouncementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnnouncementsTable::configure($table);
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
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
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
