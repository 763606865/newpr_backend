<?php

namespace App\Filament\Resources\Cms\Announcements\Tables;

use App\Enums\CmsAnnouncementType;
use App\Enums\CmsPublishStatus;
use App\Filament\Resources\Cms\CmsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                CmsTable::cityColumn('city_code', '城市编码'),
                TextColumn::make('title')->label('公告标题')->searchable(),
                CmsTable::enumBadge('type', '公告类型', CmsAnnouncementType::class, [
                    1 => 'gray',
                    2 => 'primary',
                    3 => 'warning',
                    4 => 'success',
                    5 => 'info',
                    6 => 'violet',
                ]),
                TextColumn::make('publisher_name')->label('发布人')->toggleable(),
                CmsTable::enumBadge('status', '状态', CmsPublishStatus::class, [1 => 'gray', 2 => 'success', 3 => 'danger']),
                IconColumn::make('is_top')->label('置顶')->boolean(),
                TextColumn::make('read_count')->label('阅读数')->numeric(),
                TextColumn::make('published_at')->label('发布时间')->dateTime(),
                TextColumn::make('updated_at')->label('更新时间')->dateTime(),
            ])
            ->filters([
                CmsTable::cityFilter(),
                CmsTable::statusFilter(CmsPublishStatus::class),
                CmsTable::quickDateRangeFilter('published_at'),
                CmsTable::dateRangeFilter('published_at', '发布时间'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                CmsTable::publishAction(),
                CmsTable::offlineAction(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
