<?php

namespace App\Filament\Resources\Cms\Announcements\Schemas;

use App\Enums\CmsAnnouncementType;
use App\Enums\CmsPublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('city_code')->label('城市编码')->maxLength(32),
                TextInput::make('title')->label('公告标题')->required(),
                TextInput::make('sub_title')->label('副标题'),
                TextInput::make('link_url')->label('公告链接'),
                Select::make('type')->label('公告类型')->options(CmsAnnouncementType::class)->enum(CmsAnnouncementType::class)->required(),
                TextInput::make('source_name')->label('来源名称'),
                TextInput::make('source_url')->label('来源地址'),
                Textarea::make('summary')->label('摘要')->rows(3),
                Textarea::make('content')->label('正文')->rows(6),
                DateTimePicker::make('published_at')->label('发布时间'),
                DateTimePicker::make('start_at')->label('生效开始时间'),
                DateTimePicker::make('end_at')->label('生效结束时间'),
                Toggle::make('is_top')->label('置顶')->default(false),
                Select::make('status')->label('状态')->options(CmsPublishStatus::class)->enum(CmsPublishStatus::class)->required(),
                TextInput::make('sort')->label('排序')->numeric()->default(0),
                KeyValue::make('extra')->label('扩展字段'),
            ]);
    }
}
