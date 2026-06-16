<?php

namespace App\Filament\Resources\Rc\UserIdentities\Tables;

use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Filament\Resources\Rc\RcTable;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserIdentitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user_id')->label('用户ID')->sortable(),
                TextColumn::make('organization_type')->label('机构类型')->placeholder('-'),
                TextColumn::make('organization_id')->label('机构ID')->placeholder('-')->sortable(),
                RcTable::enumBadge('identity_type', '身份类型', RcIdentityType::class),
                TextColumn::make('identity_name')->label('身份名称')->searchable(),
                TextColumn::make('organization_name')->label('所属机构')->placeholder('-')->searchable(),
                TextColumn::make('job_title')->label('岗位头衔')->placeholder('-'),
                RcTable::integerBooleanBadge('is_default', '默认'),
                RcTable::enumBadge('status', '状态', RcIdentityStatus::class),
                TextColumn::make('updated_at')->label('更新时间')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('identity_type')
                    ->label('身份类型')
                    ->options(RcIdentityType::class),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(RcIdentityStatus::class),
                Filter::make('updated_range')
                    ->label('更新时间')
                    ->schema([
                        DatePicker::make('from')->label('开始'),
                        DatePicker::make('until')->label('结束'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('updated_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('updated_at', '<=', $data['until']),
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
