<?php

namespace App\Filament\Resources\ContactInquiries\Tables;

use App\Enums\RcContactInquiryStatus;
use App\Enums\RcContactProduct;
use App\Filament\Resources\Rc\RcTable;
use App\Models\AdminUser;
use App\Models\ContactInquiry;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactInquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('姓名/称呼')->searchable(),
                TextColumn::make('phone')->label('手机号')->searchable()->copyable(),
                TextColumn::make('company_name')->label('公司名称')->searchable()->placeholder('-'),
                TextColumn::make('source')->label('来源')->searchable()->placeholder('-')->badge(),
                RcTable::enumBadge('product', '咨询产品', RcContactProduct::class, [
                    RcContactProduct::RecruitmentService->value => 'primary',
                    RcContactProduct::CampusRecruitment->value => 'info',
                    RcContactProduct::TalentService->value => 'warning',
                    RcContactProduct::Other->value => 'gray',
                ]),
                TextColumn::make('content')
                    ->label('申请内容')
                    ->limit(40)
                    ->wrap()
                    ->tooltip(fn (ContactInquiry $record): string => $record->content),
                RcTable::enumBadge('status', '回访状态', RcContactInquiryStatus::class, [
                    RcContactInquiryStatus::Pending->value => 'warning',
                    RcContactInquiryStatus::FollowedUp->value => 'success',
                ]),
                TextColumn::make('followUpAdmin.name')->label('跟进人员')->placeholder('-'),
                TextColumn::make('followed_up_at')->label('回访时间')->dateTime()->sortable()->placeholder('-'),
                TextColumn::make('submitted_at')->label('申请时间')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('product')
                    ->label('咨询产品')
                    ->options(RcContactProduct::class),
                SelectFilter::make('status')
                    ->label('回访状态')
                    ->options(RcContactInquiryStatus::class),
                SelectFilter::make('source')
                    ->label('信息来源')
                    ->options(fn (): array => ContactInquiry::query()
                        ->whereNotNull('source')
                        ->distinct()
                        ->orderBy('source')
                        ->pluck('source', 'source')
                        ->all()),
                Filter::make('submitted_range')
                    ->label('申请时间')
                    ->schema([
                        DatePicker::make('from')->label('开始日期'),
                        DatePicker::make('until')->label('结束日期'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('submitted_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('submitted_at', '<=', $data['until']),
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('followUp')
                    ->label('跟进')
                    ->icon('heroicon-o-phone-arrow-up-right')
                    ->color('success')
                    ->authorize('update')
                    ->visible(fn (ContactInquiry $record): bool => $record->status === RcContactInquiryStatus::Pending)
                    ->schema([
                        Textarea::make('follow_up_note')
                            ->label('跟进备注')
                            ->rows(5)
                            ->required()
                            ->maxLength(2000),
                    ])
                    ->action(function (array $data, ContactInquiry $record): void {
                        $admin = auth('admin')->user();

                        if (! $admin instanceof AdminUser) {
                            return;
                        }

                        $record->markAsFollowedUp($admin, (string) $data['follow_up_note']);

                        Notification::make()
                            ->title('已标记为回访')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->toolbarActions([]);
    }
}
