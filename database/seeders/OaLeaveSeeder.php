<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Oa\LeaveBalance;
use App\Models\Oa\LeaveType;
use Illuminate\Database\Seeder;

class OaLeaveSeeder extends Seeder
{
    public function run(): void
    {
        /** @var Company $company */
        $company = Company::query()->first();
        $employee = $company?->employees()->first();

        if (! $company || ! $employee) {
            return;
        }

        $leaveType = LeaveType::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'ANNUAL',
            ],
            [
                'name' => '年假',
                'deduction_type' => 1,
                'unit_type' => 1,
                'min_duration' => 0.5,
                'need_attachment' => 0,
                'allow_negative' => 0,
                'status' => 1,
            ],
        );

        LeaveBalance::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => (int) now()->format('Y'),
            ],
            [
                'valid_start_date' => now()->startOfYear()->toDateString(),
                'valid_end_date' => now()->endOfYear()->toDateString(),
                'total_days' => 10,
                'used_days' => 0,
                'balance_days' => 10,
            ],
        );
    }
}
