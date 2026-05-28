<?php

namespace App\Filament\Resources\Cms\ArticleTags\Tables;

use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\CmsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ArticleTagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('name')->label('标签名称')->searchable(),
                TextColumn::make('slug')->label('标签别名')->placeholder('-'),
                CmsTable::enumBadge('status', '状态', CmsStatus::class, [1 => 'success', 0 => 'gray']),
                TextColumn::make('sort')->label('排序')->sortable(),
            ])
            ->filters([
                CmsTable::statusFilter(CmsStatus::class),
                CmsTable::quickDateRangeFilter('updated_at'),
                CmsTable::dateRangeFilter('updated_at', '更新时间'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                CmsTable::enableAction(),
                CmsTable::disableAction(),
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
