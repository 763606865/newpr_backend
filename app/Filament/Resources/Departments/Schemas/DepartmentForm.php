<?php

namespace App\Filament\Resources\Departments\Schemas;

use App\Enums\DepartmentType;
use App\Models\Oa\Company;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        $companies = Company::query()->pluck('name', 'id');
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('所属企业')
                    ->options($companies)->searchable()->required(),
                SelectTree::make('parent_id')
                    ->label('所属部门')
                    ->relationship('parent', 'name', 'parent_id')
                    ->placeholder('请选择部门')
                    ->defaultOpenLevel(3)
                    ->default(0)
                    ->searchable() // 开启搜索
                    ->enableBranchNode()
                    ->afterStateHydrated(fn($c, $s) => $s && $c->state($s)),
                Radio::make('type')
                    ->label('类型')
                    ->options(DepartmentType::class)
                    ->default(DepartmentType::Function->value)
                    ->required(),
                TextInput::make('name')->label('名称')->required(),
                TextInput::make('sort')->label('排序号')->default(99)->numeric(),
                Textarea::make('remark')->label('备注'),
            ]);
    }
}
