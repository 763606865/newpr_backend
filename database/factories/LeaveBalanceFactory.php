<?php

namespace Database\Factories;

use App\Models\Oa\LeaveBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveBalance>
 */
class LeaveBalanceFactory extends Factory
{
    protected $model = LeaveBalance::class;

    public function definition(): array
    {
        $totalDays = fake()->randomFloat(2, 1, 30);
        $usedDays = fake()->randomFloat(2, 0, $totalDays);

        return [
            'company_id' => 1,
            'user_id' => 1,
            'leave_type_id' => 1,
            'year' => (int) now()->format('Y'),
            'valid_start_date' => now()->startOfYear()->toDateString(),
            'valid_end_date' => now()->endOfYear()->toDateString(),
            'total_days' => $totalDays,
            'used_days' => $usedDays,
            'balance_days' => round($totalDays - $usedDays, 2),
            'overtime_source_id' => fake()->optional()->numberBetween(1, 999999),
        ];
    }
}
