<?php

namespace App\Filament\Resources\Cms\BannerPositions\Schemas;

use App\Enums\CmsStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BannerPositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('版位名称')->required()->maxLength(255),
                TextInput::make('code')->label('版位编码')->required()->maxLength(64),
                TextInput::make('width')->label('建议宽度')->numeric(),
                TextInput::make('height')->label('建议高度')->numeric(),
                TextInput::make('sort')->label('排序')->numeric()->default(0),
                Select::make('status')->label('状态')->options(CmsStatus::class)->enum(CmsStatus::class)->required(),
                TextInput::make('remark')->label('备注')->maxLength(255),
            ]);
    }
}
