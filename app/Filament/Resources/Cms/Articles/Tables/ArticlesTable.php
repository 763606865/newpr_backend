<?php

namespace App\Filament\Resources\Cms\Articles\Tables;

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

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('category.name')->label('分类')->placeholder('-'),
                CmsTable::cityColumn(),
                TextColumn::make('title')->label('标题')->searchable(),
                CmsTable::enumBadge('status', '状态', CmsPublishStatus::class, [1 => 'gray', 2 => 'success', 3 => 'danger']),
                IconColumn::make('is_top')->label('置顶')->boolean(),
                IconColumn::make('is_recommend')->label('推荐')->boolean(),
                TextColumn::make('published_at')->label('发布时间')->dateTime(),
                TextColumn::make('view_count')->label('浏览量')->sortable(),
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
