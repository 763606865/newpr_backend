<?php

namespace Tests\Unit\Models;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_descendant_and_self_ids_includes_group_and_subsidiaries(): void
    {
        $group = Company::query()->create([
            'name' => '示例集团',
            'credit_code' => '91360100MA0000000G',
            'status' => CompanyStatus::Enabled,
            'depth' => 1,
        ]);

        $subsidiaryA = Company::query()->create([
            'parent_id' => $group->id,
            'depth' => 2,
            'name' => '示例子公司A',
            'credit_code' => '91360100MA0000000A',
            'status' => CompanyStatus::Enabled,
        ]);

        $subsidiaryB = Company::query()->create([
            'parent_id' => $group->id,
            'depth' => 2,
            'name' => '示例子公司B',
            'credit_code' => '91360100MA0000000B',
            'status' => CompanyStatus::Enabled,
        ]);

        $this->assertEquals(
            [$group->id, $subsidiaryA->id, $subsidiaryB->id],
            $group->descendantAndSelfIds()->sort()->values()->all(),
        );

        $this->assertEquals(
            [$subsidiaryA->id],
            $subsidiaryA->descendantAndSelfIds()->all(),
        );
    }
}
