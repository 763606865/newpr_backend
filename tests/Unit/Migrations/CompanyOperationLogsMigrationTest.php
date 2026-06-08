<?php

namespace Tests\Unit\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompanyOperationLogsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_operation_logs_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('company_operation_logs'));
        $this->assertTrue(Schema::hasColumns('company_operation_logs', [
            'id',
            'company_id',
            'operator_id',
            'operator_type',
            'action',
            'summary',
            'changes',
            'ip',
            'user_agent',
            'extra',
            'created_at',
        ]));
    }

    public function test_companies_table_has_auditor_id_column(): void
    {
        $this->assertTrue(Schema::hasColumns('companies', [
            'auditor_id',
        ]));
    }
}
