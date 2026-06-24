<?php

namespace App\Filament\Resources\Cms\HomeRecommendations\Tables;

use App\Enums\CmsHomeRecommendationModuleType;
use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\CmsTable;
use App\Models\Company;
use App\Models\Rc\Job;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HomeRecommendationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->with([
                    'recommendable' => function ($morphTo): void {
                        $morphTo->morphWith([
                            Job::class => ['company'],
                            Company::class => ['profile'],
                        ]);
                    },
                ]);
            })
            ->columns([
                TextColumn::make('id')->label('ID'),
                CmsTable::enumBadge('module_type', '推荐模块', CmsHomeRecommendationModuleType::class, [
                    1 => 'danger',
                    2 => 'warning',
                    3 => 'info',
                ]),
                TextColumn::make('recommendable_label')
                    ->label('推荐对象')
                    ->state(function ($record): string {
                        $recommendable = $record->recommendable;

                        if ($recommendable instanceof Job) {
                            return $recommendable->title;
                        }

                        if ($recommendable instanceof Company) {
                            return $recommendable->profile?->short_name ?: $recommendable->name;
                        }

                        return '-';
                    }),
                TextColumn::make('title')
                    ->label('推荐标题')
                    ->placeholder('-'),
                ImageColumn::make('cover_image')
                    ->label('展示图')
                    ->disk('oss')
                    ->visibility('public')
                    ->square(),
                CmsTable::cityColumn(),
                TextColumn::make('start_at')->label('开始时间')->dateTime()->placeholder('-'),
                TextColumn::make('end_at')->label('结束时间')->dateTime()->placeholder('-'),
                CmsTable::enumBadge('status', '状态', CmsStatus::class, [1 => 'success', 0 => 'gray']),
                TextColumn::make('sort')->label('排序')->sortable(),
                TextColumn::make('order_id')->label('订单ID')->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('module_type')
                    ->label('推荐模块')
                    ->options(CmsHomeRecommendationModuleType::class),
                CmsTable::statusFilter(CmsStatus::class),
                CmsTable::cityFilter(),
                CmsTable::quickDateRangeFilter('start_at'),
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
