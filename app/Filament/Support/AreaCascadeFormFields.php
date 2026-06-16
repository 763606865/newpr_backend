<?php

namespace App\Filament\Support;

use App\Models\Area;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

final class AreaCascadeFormFields
{
    /**
     * @return array<int, Component>
     */
    public static function make(
        string $persistedField = 'city_code',
        string $provinceField = 'province_code',
        string $cityField = 'area_city_code',
        string $districtField = 'district_code',
    ): array {
        return [
            Select::make($provinceField)
                ->label('省份')
                ->options(fn (): array => Area::provinceOptions())
                ->searchable()
                ->preload()
                ->placeholder('请选择省份')
                ->dehydrated(false)
                ->live()
                ->afterStateUpdated(function (mixed $state, Set $set) use ($cityField, $districtField, $persistedField): void {
                    $set($cityField, null);
                    $set($districtField, null);
                    $set($persistedField, filled($state) ? $state : null);
                }),
            Select::make($cityField)
                ->label('城市')
                ->options(fn (Get $get): array => Area::cityOptions($get($provinceField)))
                ->searchable()
                ->placeholder('请选择城市')
                ->dehydrated(false)
                ->disabled(fn (Get $get): bool => blank($get($provinceField)))
                ->live()
                ->afterStateUpdated(function (mixed $state, Set $set, Get $get) use ($districtField, $persistedField, $provinceField): void {
                    $set($districtField, null);
                    $set($persistedField, Area::resolveAnnouncementAreaCode($get($provinceField), $state, null));
                }),
            Select::make($districtField)
                ->label('区县')
                ->options(fn (Get $get): array => Area::districtOptions($get($provinceField), $get($cityField)))
                ->searchable()
                ->placeholder('请选择区县')
                ->dehydrated(false)
                ->disabled(fn (Get $get): bool => blank($get($provinceField)))
                ->live()
                ->afterStateUpdated(function (mixed $state, Set $set, Get $get) use ($cityField, $persistedField, $provinceField): void {
                    $set($persistedField, Area::resolveAnnouncementAreaCode($get($provinceField), $get($cityField), $state));
                }),
            Hidden::make($persistedField),
        ];
    }

    /**
     * 省 / 市二级联动，持久化字段保存市级 areas.code。
     *
     * @return array<int, Component>
     */
    public static function makeTwoLevel(
        string $persistedField = 'city_code',
        string $provinceField = 'province_code',
        string $cityField = 'area_city_code',
    ): array {
        return [
            Select::make($provinceField)
                ->label('省份')
                ->options(fn (): array => Area::provinceOptions())
                ->searchable()
                ->preload()
                ->placeholder('全站可用')
                ->dehydrated(false)
                ->live()
                ->afterStateUpdated(function (mixed $state, Set $set) use ($cityField, $persistedField): void {
                    $set($cityField, null);
                    $set($persistedField, null);
                }),
            Select::make($cityField)
                ->label('城市')
                ->options(fn (Get $get): array => Area::cityOptions($get($provinceField)))
                ->searchable()
                ->placeholder('全站可用')
                ->dehydrated(false)
                ->disabled(fn (Get $get): bool => blank($get($provinceField)))
                ->live()
                ->afterStateUpdated(function (mixed $state, Set $set) use ($persistedField): void {
                    $set($persistedField, filled($state) ? $state : null);
                }),
            Hidden::make($persistedField),
        ];
    }
}
