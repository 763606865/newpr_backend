<?php

namespace App\Filament\Resources\Rc\AssetDefinitions\Tables;

use App\Enums\RcAssetDefinitionStatus;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetType;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetDefinitionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('asset_name')
                    ->label('权益名称')
                    ->searchable(),
                TextColumn::make('asset_code')
                    ->label('权益编码')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('owner_type')
                    ->label('适用主体')
                    ->badge(),
                TextColumn::make('asset_type')
                    ->label('权益类型')
                    ->badge(),
                TextColumn::make('consume_scene')
                    ->label('消费场景')
                    ->placeholder('-'),
                TextColumn::make('unit')
                    ->label('单位'),
                TextColumn::make('default_duration')
                    ->label('默认有效期')
                    ->formatStateUsing(static fn (int $state): string => $state === 0 ? '永久' : $state.' 天'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (RcAssetDefinitionStatus $state): string => match ($state) {
                        RcAssetDefinitionStatus::Enabled => 'success',
                        RcAssetDefinitionStatus::Disabled => 'gray',
                    }),
                TextColumn::make('sort')
                    ->label('排序')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('owner_type')
                    ->label('适用主体')
                    ->options(RcAssetOwnerType::class),
                SelectFilter::make('asset_type')
                    ->label('权益类型')
                    ->options(RcAssetType::class),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(RcAssetDefinitionStatus::class),
            ])
            ->defaultSort('sort')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
