<?php

namespace App\Filament\Resources\Cms\AdSlots\Tables;

use App\Enums\CmsAdType;
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

class AdSlotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('name')->label('广告位名称')->searchable(),
                TextColumn::make('code')->label('广告位编码')->searchable(),
                CmsTable::enumBadge('type', '类型', CmsAdType::class, [1 => 'primary', 2 => 'warning', 3 => 'gray']),
                CmsTable::enumBadge('status', '状态', CmsStatus::class, [1 => 'success', 0 => 'gray']),
                TextColumn::make('sort')->label('排序')->sortable(),
                TextColumn::make('updated_at')->label('更新时间')->dateTime(),
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
