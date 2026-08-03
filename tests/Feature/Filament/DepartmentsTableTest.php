<?php

namespace Tests\Feature\Filament;

use App\Enums\DepartmentType;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class DepartmentsTableTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    public function test_department_list_does_not_use_recursive_cte(): void
    {
        $this->actingAsFilamentAdmin([
            'ViewAny:Department',
            'View:Department',
            'Create:Department',
            'Update:Department',
            'Delete:Department',
        ]);

        $company = Company::query()->create([
            'name' => '测试企业',
            'credit_code' => '91360100MA0000000X',
            'status' => 1,
        ]);
        $parent = Department::query()->create([
            'company_id' => $company->id,
            'name' => '总部',
            'type' => DepartmentType::Function,
            'sort' => 1,
        ]);
        $child = Department::query()->create([
            'company_id' => $company->id,
            'parent_id' => $parent->id,
            'name' => '技术部',
            'type' => DepartmentType::Function,
            'sort' => 2,
        ]);

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = mb_strtolower($query->sql);
        });

        Livewire::test(ListDepartments::class)
            ->assertCanSeeTableRecords([$parent, $child]);

        $this->assertNotEmpty($queries);
        $this->assertFalse(collect($queries)->contains(
            fn (string $sql): bool => str_contains($sql, 'with recursive'),
        ));
    }
}
