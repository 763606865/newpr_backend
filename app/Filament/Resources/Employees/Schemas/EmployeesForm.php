<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Oa\Employee;
use App\Models\User;
use App\Models\Oa\Department;
use App\Models\Oa\Position;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;

class EmployeesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('关联用户')
                    ->relationship('user', 'name')
                    ->live()
                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                        if (blank($state)) {
                            $set('mobile', null);

                            return;
                        }
                        /** @var User $user */
                        $user = User::query()->find($state);
                        $set('mobile', $user?->phone);
                        $set('email', $user?->email);
                        $set('real_name', $user?->name);
                        $set('avatar', $user?->avatar);
                    })
                    ->searchable(['name', 'phone'])
                    ->optionsLimit(20)
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => sprintf('%s（%s）', $record->name ?: $record->nickname ?: '未命名用户', $record->phone))
                    ->searchPrompt('请输入姓名或手机号搜索用户')
                    ->noSearchResultsMessage('未找到匹配的用户')
                    ->required(),
                Select::make('company_id')
                    ->label('所属企业')
                    ->relationship('company', 'name')
                    ->live()
                    ->afterStateUpdated(function (mixed $state, callable $set): void {
                        if (blank($state)) {
                            $set('department_id', null);
                            $set('position_id', null);

                            return;
                        }

                        $set('department_id', null);
                        $set('position_id', null);
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('department_id')
                    ->label('所属部门')
                    ->options(function (callable $get): array {
                        $companyId = $get('company_id');

                        if (blank($companyId)) {
                            return [];
                        }

                        return Department::query()
                            ->where('company_id', $companyId)
                            ->treeOf(fn (Builder $rootQuery): Builder => $rootQuery->where('parent_id', 0))
                            ->depthFirst()
                            ->get()
                            ->mapWithKeys(function (Department $department): array {
                                $level = max(0, (int) ($department->tree_depth ?? 0));
                                $label = str_repeat('|- ', $level).$department->name;

                                return [$department->id => $label];
                            })
                            ->all();
                    })
                    ->disabled(fn (callable $get): bool => blank($get('company_id')))
                    ->searchable()
                    ->preload(),
                Select::make('position_id')
                    ->label('所属岗位')
                    ->options(function (callable $get): array {
                        $companyId = $get('company_id');

                        if (blank($companyId)) {
                            return [];
                        }

                        return Position::query()
                            ->where('company_id', $companyId)
                            ->orderBy('sort')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->disabled(fn (callable $get): bool => blank($get('company_id')))
                    ->searchable()
                    ->preload(),
                TextInput::make('employee_no')
                    ->label('员工工号')
                    ->suffixAction(
                        Action::make('generateEmployeeNo')
                            ->label('自动生成')
                            ->button()
                            ->color('gray')
                            ->action(function (Set $schemaSet): void {
                                $schemaSet('employee_no', self::generateEmployeeNo());
                            })
                    )
                    ->maxLength(60),
                TextInput::make('real_name')
                    ->label('员工姓名')
                    ->required()
                    ->maxLength(60),
                FileUpload::make('avatar')
                    ->label('头像地址')
                    ->image()
                    ->required(),
                TextInput::make('email')
                    ->label('邮箱')
                    ->email()
                    ->maxLength(100),
                TextInput::make('mobile')
                    ->label('手机号')
                    ->tel()
                    ->maxLength(20),
                Select::make('status')
                    ->label('状态')
                    ->options([
                        1 => '在职',
                        2 => '离职',
                    ])
                    ->default(1)
                    ->required(),
                DatePicker::make('entry_time')
                    ->label('加入时间')
                    ->seconds(false),
            ]);
    }

    protected static function generateEmployeeNo(): string
    {
        do {
            $employeeNo = 'EMP'.now()->format('YmdHis').str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (Employee::query()->withTrashed()->where('employee_no', $employeeNo)->exists());

        return $employeeNo;
    }
}
