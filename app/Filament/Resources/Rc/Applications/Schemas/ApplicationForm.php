<?php

namespace App\Filament\Resources\Rc\Applications\Schemas;

use App\Enums\RcApplicationSourceType;
use App\Enums\RcApplicationStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')->label('ID')->disabled()->dehydrated(false),
                TextInput::make('company_id')->label('企业ID')->disabled()->dehydrated(false),
                TextInput::make('job_id')->label('职位ID')->disabled()->dehydrated(false),
                TextInput::make('candidate_user_id')->label('候选人用户ID')->disabled()->dehydrated(false),
                TextInput::make('resume_id')->label('简历ID')->disabled()->dehydrated(false),
                Select::make('current_stage_id')
                    ->label('当前阶段')
                    ->relationship('currentStage', 'name')
                    ->searchable(),
                Select::make('source_type')
                    ->label('来源类型')
                    ->options(RcApplicationSourceType::class)
                    ->enum(RcApplicationSourceType::class)
                    ->required(),
                Select::make('status')
                    ->label('投递状态')
                    ->options(RcApplicationStatus::class)
                    ->enum(RcApplicationStatus::class)
                    ->required(),
                DateTimePicker::make('applied_at')->label('投递时间'),
                DateTimePicker::make('withdrawn_at')->label('撤回时间'),
                KeyValue::make('resume_snapshot')->label('简历快照')->columnSpanFull(),
                KeyValue::make('extra')->label('扩展字段')->columnSpanFull(),
            ]);
    }
}
