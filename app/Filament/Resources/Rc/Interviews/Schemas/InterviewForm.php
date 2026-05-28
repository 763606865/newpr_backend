<?php

namespace App\Filament\Resources\Rc\Interviews\Schemas;

use App\Enums\RcInterviewMode;
use App\Enums\RcInterviewResult;
use App\Enums\RcInterviewStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InterviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')->label('ID')->disabled()->dehydrated(false),
                TextInput::make('company_id')->label('企业ID')->disabled()->dehydrated(false),
                TextInput::make('application_id')->label('投递ID')->disabled()->dehydrated(false),
                TextInput::make('stage_id')->label('阶段ID')->numeric(),
                TextInput::make('interviewer_user_id')->label('面试官用户ID')->numeric(),
                TextInput::make('interviewer_name')->label('面试官姓名')->maxLength(50),
                DateTimePicker::make('interview_at')->label('面试时间'),
                TextInput::make('duration_mins')->label('面试时长(分钟)')->numeric()->minValue(1),
                Select::make('mode')
                    ->label('面试方式')
                    ->options(RcInterviewMode::class)
                    ->enum(RcInterviewMode::class)
                    ->required(),
                Select::make('status')
                    ->label('面试状态')
                    ->options(RcInterviewStatus::class)
                    ->enum(RcInterviewStatus::class)
                    ->required(),
                Select::make('result')
                    ->label('面试结果')
                    ->options(RcInterviewResult::class)
                    ->enum(RcInterviewResult::class)
                    ->required(),
                TextInput::make('location')->label('面试地点')->maxLength(255),
                TextInput::make('meeting_url')->label('线上会议链接')->url()->maxLength(255),
                Textarea::make('note')->label('备注')->rows(3)->columnSpanFull(),
                KeyValue::make('extra')->label('扩展字段')->columnSpanFull(),
            ]);
    }
}
