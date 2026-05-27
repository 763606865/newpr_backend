<?php

namespace App\Filament\Resources\Cms\Articles\Schemas;

use App\Enums\CmsPublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')->label('分类')->relationship('category', 'name')->searchable(),
                TextInput::make('city_code')->label('城市编码')->maxLength(32),
                TextInput::make('title')->label('标题')->required(),
                TextInput::make('sub_title')->label('副标题'),
                TextInput::make('slug')->label('别名')->maxLength(191),
                TextInput::make('cover')->label('封面图'),
                Textarea::make('summary')->label('摘要')->rows(3),
                TextInput::make('author')->label('作者'),
                TextInput::make('source_name')->label('来源名称'),
                TextInput::make('source_url')->label('来源链接'),
                Toggle::make('is_top')->label('置顶')->default(false),
                Toggle::make('is_recommend')->label('推荐')->default(false),
                Select::make('status')->label('状态')->options(CmsPublishStatus::class)->enum(CmsPublishStatus::class)->required(),
                DateTimePicker::make('published_at')->label('发布时间'),
                TextInput::make('view_count')->label('浏览量')->numeric()->default(0),
                Select::make('tags')
                    ->label('标签')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload(),
                TextInput::make('seo_keywords')->label('SEO关键词'),
                Textarea::make('seo_description')->label('SEO描述')->rows(3),
                KeyValue::make('extra')->label('扩展字段'),
            ]);
    }
}
