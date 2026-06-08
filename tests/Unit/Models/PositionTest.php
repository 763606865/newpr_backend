<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_leader_defaults_to_false_and_casts_to_boolean(): void
    {
        $company = Company::query()->create([
            'name' => '测试企业有限公司',
            'credit_code' => '91360100MA0000000X',
        ]);

        $position = Position::query()->create([
            'company_id' => $company->id,
            'name' => '普通岗位',
            'code' => 'STAFF',
        ]);

        $this->assertFalse($position->is_leader);
        $this->assertIsBool($position->is_leader);

        $position->update(['is_leader' => true]);

        $this->assertTrue($position->fresh()->is_leader);
    }
}
