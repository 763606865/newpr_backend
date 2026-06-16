<?php

namespace App\Filament\Resources\Cms\Tags\Tables;

use App\Enums\CmsStatus;
use App\Enums\CmsTagCategory;
use App\Filament\Resources\Cms\CmsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('category')->label('分类')->badge()->searchable(),
                TextColumn::make('name')->label('标签名称')->searchable(),
                TextColumn::make('slug')->label('别名')->toggleable(),
                CmsTable::enumBadge('status', '状态', CmsStatus::class, [0 => 'danger', 1 => 'success']),
                TextColumn::make('sort')->label('排序')->numeric(),
                TextColumn::make('updated_at')->label('更新时间')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('分类')
                    ->options(CmsTagCategory::class),
                CmsTable::statusFilter(CmsStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
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
