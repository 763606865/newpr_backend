<?php

namespace App\Filament\Resources\Rc\Resumes\Schemas;

use App\Enums\RcResumeSourceType;
use App\Enums\RcResumeStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ResumeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')->label('ID')->disabled()->dehydrated(false),
                TextInput::make('user_id')->label('用户ID')->disabled()->dehydrated(false),
                TextInput::make('resume_no')->label('简历编号')->disabled()->dehydrated(false),
                TextInput::make('title')->label('简历标题')->required(),
                Select::make('source_type')
                    ->label('来源类型')
                    ->options(RcResumeSourceType::class)
                    ->enum(RcResumeSourceType::class)
                    ->required(),
                TextInput::make('file_url')->label('文件地址'),
                TextInput::make('file_name')->label('文件名称'),
                TextInput::make('file_ext')->label('文件后缀')->maxLength(16),
                Textarea::make('text_content')->label('简历文本')->rows(8),
                Toggle::make('is_primary')->label('设为主简历')->inline(false),
                Select::make('status')
                    ->label('状态')
                    ->options(RcResumeStatus::class)
                    ->enum(RcResumeStatus::class)
                    ->required(),
            ]);
    }
}
