<?php

namespace App\Filament\Resources\Cms\FriendLinks\Schemas;

use App\Enums\CmsOpenTarget;
use App\Enums\CmsStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FriendLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('city_code')->label('城市编码')->maxLength(32),
                TextInput::make('name')->label('友链名称')->required(),
                TextInput::make('url')->label('友链地址')->required(),
                TextInput::make('logo')->label('Logo'),
                Select::make('target')->label('打开方式')->options(CmsOpenTarget::class)->enum(CmsOpenTarget::class)->required(),
                TextInput::make('rel')->label('rel属性'),
                TextInput::make('description')->label('描述'),
                DateTimePicker::make('start_at')->label('生效开始时间'),
                DateTimePicker::make('end_at')->label('生效结束时间'),
                Select::make('status')->label('状态')->options(CmsStatus::class)->enum(CmsStatus::class)->required(),
                TextInput::make('sort')->label('排序')->numeric()->default(0),
                KeyValue::make('extra')->label('扩展字段'),
            ]);
    }
}
