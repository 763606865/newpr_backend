<?php

namespace App\Filament\Resources\Cms\Articles\Schemas;

use App\Enums\CmsArticleContentType;
use App\Enums\CmsPublishStatus;
use App\Filament\Support\AreaCascadeFormFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')->label('分类')->relationship('category', 'name')->searchable(),
                ...AreaCascadeFormFields::makeTwoLevel(),
                TextInput::make('title')->label('标题')->required(),
                TextInput::make('sub_title')->label('副标题'),
                TextInput::make('slug')->label('别名')->maxLength(191),
                FileUpload::make('cover')
                    ->label('封面图')
                    ->image()
                    ->disk('oss')
                    ->directory('article')
                    ->visibility(config('filesystems.disks.oss.visibility', 'public'))
                    ->formatStateUsing(static fn (mixed $state): ?string => self::normalizeOssPath($state))
                    ->maxSize(5120),
                Textarea::make('summary')->label('摘要')->rows(3),
                Select::make('content_type')
                    ->label('正文类型')
                    ->options(CmsArticleContentType::class)
                    ->enum(CmsArticleContentType::class)
                    ->default(CmsArticleContentType::Html)
                    ->live(),
                RichEditor::make('body_html')
                    ->label('正文')
                    ->columnSpanFull()
                    ->hidden(fn (Get $get): bool => self::isMarkdownContentType($get('content_type'))),
                Textarea::make('body_markdown')
                    ->label('正文')
                    ->rows(15)
                    ->columnSpanFull()
                    ->hidden(fn (Get $get): bool => ! self::isMarkdownContentType($get('content_type'))),
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
            ]);
    }

    private static function isMarkdownContentType(mixed $contentType): bool
    {
        if ($contentType instanceof CmsArticleContentType) {
            return $contentType === CmsArticleContentType::Markdown;
        }

        return (int) $contentType === CmsArticleContentType::Markdown->value;
    }

    private static function normalizeOssPath(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        if (is_array($state)) {
            $state = array_values($state)[0] ?? null;
        }

        if (! is_string($state) || $state === '') {
            return null;
        }

        if (str_starts_with($state, 'http://') || str_starts_with($state, 'https://')) {
            $path = parse_url($state, PHP_URL_PATH);

            return is_string($path) ? ltrim($path, '/') : null;
        }

        return ltrim($state, '/');
    }
}
