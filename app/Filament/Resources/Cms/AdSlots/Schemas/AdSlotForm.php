<?php

namespace App\Filament\Resources\Cms\AdSlots\Schemas;

use App\Enums\CmsAdType;
use App\Enums\CmsStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AdSlotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('广告位名称')->required(),
                TextInput::make('code')->label('广告位编码')->required()->maxLength(64),
                Select::make('type')->label('类型')->options(CmsAdType::class)->enum(CmsAdType::class)->required(),
                TextInput::make('width')->label('建议宽度')->numeric(),
                TextInput::make('height')->label('建议高度')->numeric(),
                Select::make('status')->label('状态')->options(CmsStatus::class)->enum(CmsStatus::class)->required(),
                TextInput::make('sort')->label('排序')->numeric()->default(0),
                TextInput::make('remark')->label('备注'),
            ]);
    }
}
