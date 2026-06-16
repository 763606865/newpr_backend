<?php

namespace App\Filament\Resources\Rc\Jobs\Schemas;

use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Enums\RcSalaryUnit;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')->label('ID')->disabled()->dehydrated(false),
                TextInput::make('company_id')->label('企业ID')->disabled()->dehydrated(false),
                TextInput::make('department_id')->label('部门ID')->disabled()->dehydrated(false),
                TextInput::make('creator_user_id')->label('创建人ID')->disabled()->dehydrated(false),
                TextInput::make('code')->label('职位编码')->disabled()->dehydrated(false),
                TextInput::make('position_code')->label('岗位编码')->maxLength(255),
                TextInput::make('title')->label('职位名称')->disabled()->dehydrated(false),
                TextInput::make('city_code')->label('城市编码')->maxLength(32),
                TextInput::make('workplace')->label('工作地点'),
                Select::make('employment_type')
                    ->label('用工类型')
                    ->options(RcJobEmploymentType::class)
                    ->enum(RcJobEmploymentType::class)
                    ->required(),
                TextInput::make('salary_min')->label('最低薪资')->numeric(),
                TextInput::make('salary_max')->label('最高薪资')->numeric(),
                Select::make('salary_unit')
                    ->label('薪资单位')
                    ->options(RcSalaryUnit::class)
                    ->enum(RcSalaryUnit::class)
                    ->required(),
                TextInput::make('headcount')->label('招聘人数')->numeric()->required(),
                Select::make('status')
                    ->label('状态')
                    ->options(RcJobStatus::class)
                    ->enum(RcJobStatus::class)
                    ->required(),
                DateTimePicker::make('published_at')->label('发布时间'),
                DateTimePicker::make('expired_at')->label('过期时间'),
                Textarea::make('description')->label('职位描述')->rows(4),
                Textarea::make('requirement')->label('职位要求')->rows(4),
                Textarea::make('benefit')->label('福利待遇')->rows(3),
            ]);
    }
}
