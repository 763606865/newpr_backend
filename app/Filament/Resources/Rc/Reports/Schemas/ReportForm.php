<?php

namespace App\Filament\Resources\Rc\Reports\Schemas;

use App\Enums\RcReportReasonType;
use App\Enums\RcReportStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\Report;
use App\Models\Rc\Resume;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('举报信息')
                    ->schema([
                        TextInput::make('id')->label('ID')->disabled()->dehydrated(false),
                        TextInput::make('user.name')->label('举报用户')->disabled()->dehydrated(false),
                        TextInput::make('creatorIdentity.identity_name')->label('举报身份')->disabled()->dehydrated(false),
                        TextInput::make('reportable_type')
                            ->label('举报对象类型')
                            ->formatStateUsing(fn (?string $state): string => self::formatReportableType($state))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('reportable_id')->label('举报对象ID')->disabled()->dehydrated(false),
                        TextInput::make('reportable_title')
                            ->label('举报对象')
                            ->formatStateUsing(fn (mixed $state, ?Report $record): string => self::formatReportableTitle($record))
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('reason_type')
                            ->label('举报原因类型')
                            ->options(RcReportReasonType::class)
                            ->enum(RcReportReasonType::class)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('reason')->label('举报原因')->disabled()->dehydrated(false),
                        Textarea::make('description')->label('举报说明')->rows(4)->disabled()->dehydrated(false)->columnSpanFull(),
                        Textarea::make('attachments')
                            ->label('举报凭证附件')
                            ->formatStateUsing(fn (mixed $state): string => self::formatJsonState($state))
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        TextInput::make('ip')->label('举报IP')->disabled()->dehydrated(false),
                        Textarea::make('user_agent')->label('User-Agent')->rows(3)->disabled()->dehydrated(false)->columnSpanFull(),
                        TextInput::make('created_at')->label('举报时间')->disabled()->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make('处理信息')
                    ->schema([
                        Select::make('status')
                            ->label('处理状态')
                            ->options(RcReportStatus::class)
                            ->enum(RcReportStatus::class)
                            ->required(),
                        TextInput::make('handler.name')->label('处理管理员')->disabled()->dehydrated(false),
                        DateTimePicker::make('handled_at')->label('处理时间')->seconds(false),
                        Textarea::make('handle_result')->label('处理结果')->rows(4)->columnSpanFull(),
                        KeyValue::make('extra')->label('扩展字段')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    private static function formatReportableType(?string $type): string
    {
        return match ($type) {
            'job' => '职位',
            'company' => '企业',
            'resume' => '简历',
            default => $type ?? '-',
        };
    }

    private static function formatReportableTitle(?Report $report): string
    {
        $reportable = $report?->reportable;

        return match (true) {
            $reportable instanceof Job => $reportable->title,
            $reportable instanceof Company => $reportable->name,
            $reportable instanceof Resume => $reportable->title,
            default => '-',
        };
    }

    private static function formatJsonState(mixed $state): string
    {
        if (blank($state)) {
            return '-';
        }

        if (is_string($state)) {
            return $state;
        }

        return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '-';
    }
}
