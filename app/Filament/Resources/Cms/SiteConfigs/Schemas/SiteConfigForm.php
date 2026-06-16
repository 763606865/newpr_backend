<?php

namespace App\Filament\Resources\Cms\SiteConfigs\Schemas;

use App\Enums\CmsStatus;
use App\Filament\Support\AreaCascadeFormFields;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SiteConfigForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('site_code')->label('站点编码')->required()->maxLength(64),
                ...AreaCascadeFormFields::make(),
                TextInput::make('name')->label('站点名称')->required(),
                TextInput::make('short_name')->label('站点简称'),
                TextInput::make('domain')->label('站点域名'),
                FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->disk('oss')
                    ->directory('site-config')
                    ->visibility(config('filesystems.disks.oss.visibility', 'public'))
                    ->formatStateUsing(static fn (mixed $state): ?string => self::normalizeOssPath($state))
                    ->maxSize(10240), // 10MB
                FileUpload::make('favicon')
                    ->label('Favicon')
                    ->image()
                    ->disk('oss')
                    ->directory('site-config')
                    ->visibility(config('filesystems.disks.oss.visibility', 'public'))
                    ->formatStateUsing(static fn (mixed $state): ?string => self::normalizeOssPath($state))
                    ->maxSize(2048), // 2MB
                TextInput::make('slogan')->label('Slogan'),
                TextInput::make('icp_no')->label('ICP备案号'),
                TextInput::make('public_security_no')->label('公安备案号'),
                TextInput::make('service_phone')->label('客服电话'),
                TextInput::make('service_email')->label('客服邮箱')->email(),
                TextInput::make('seo_title')->label('SEO标题'),
                TextInput::make('seo_keywords')->label('SEO关键词'),
                Textarea::make('seo_description')->label('SEO描述')->rows(3),
                Select::make('status')->label('状态')->options(CmsStatus::class)->enum(CmsStatus::class)->required(),
                KeyValue::make('theme_config')->label('主题配置'),
                KeyValue::make('extra')->label('扩展配置'),
            ]);
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
