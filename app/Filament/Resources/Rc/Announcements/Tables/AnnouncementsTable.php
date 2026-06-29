<?php

namespace App\Filament\Resources\Rc\Announcements\Tables;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsPublishStatus;
use App\Enums\RcAnnouncementApplyDeadlineType;
use App\Enums\RcEducationLevel;
use App\Filament\Resources\Cms\CmsTable;
use App\Filament\Resources\Rc\RcTable;
use App\Models\Area;
use App\Models\Rc\Announcement;
use Filament\Actions\Action;
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
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('公告标题')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('publisher_name')
                    ->label('发布人')
                    ->searchable(),
                RcTable::enumBadge('publisher_type', '发布人类型', CmsAnnouncementPublisherType::class),
                TextColumn::make('employment_type_labels')
                    ->label('工作类型')
                    ->badge()
                    ->getStateUsing(fn (Announcement $record): string => implode('、', $record->employmentTypeLabels()) ?: '-'),
                TextColumn::make('graduation_year_labels')
                    ->label('面向届别')
                    ->getStateUsing(fn (Announcement $record): string => implode('/', $record->graduationYearLabels()) ?: '-'),
                RcTable::enumBadge('education_level', '学历', RcEducationLevel::class),
                TextColumn::make('location_label')
                    ->label('工作地点')
                    ->getStateUsing(function (Announcement $record): string {
                        if ($record->is_nationwide) {
                            return '全国';
                        }

                        $names = Area::query()
                            ->whereIn('code', $record->cities()->pluck('city_code'))
                            ->pluck('name')
                            ->all();

                        return $names === [] ? '-' : implode('、', $names);
                    }),
                TextColumn::make('apply_status')
                    ->label('报名状态')
                    ->badge()
                    ->getStateUsing(fn (Announcement $record): string => $record->applyStatusLabel())
                    ->color(fn (Announcement $record): string => match ($record->applyStatusLabel()) {
                        '正在报名' => 'success',
                        '即将截止' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('apply_end_at')
                    ->label('截止时间')
                    ->formatStateUsing(function (Announcement $record): string {
                        if ($record->apply_deadline_type === RcAnnouncementApplyDeadlineType::UntilFilled) {
                            return '招满即止';
                        }

                        return $record->apply_end_at?->format('Y-m-d H:i') ?? '-';
                    }),
                CmsTable::enumBadge('status', '状态', CmsPublishStatus::class, [1 => 'gray', 2 => 'success', 3 => 'danger']),
                IconColumn::make('is_top')
                    ->label('置顶')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('发布时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                CmsTable::statusFilter(CmsPublishStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('openLink')
                    ->label('打开链接')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Announcement $record): ?string => $record->link_url)
                    ->openUrlInNewTab()
                    ->visible(fn (Announcement $record): bool => filled($record->link_url)),
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
            ])
            ->defaultSort('published_at', 'desc');
    }
}
