<?php

namespace Database\Factories;

use App\Models\Oa\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => fake()->randomElement(['年假', '病假', '事假', '调休假']),
            'code' => strtoupper(fake()->unique()->bothify('TYPE_##??')),
            'deduction_type' => fake()->randomElement([1, 2, 3]),
            'unit_type' => fake()->randomElement([1, 2]),
            'min_duration' => fake()->randomElement([0.5, 1, 2]),
            'need_attachment' => fake()->boolean() ? 1 : 0,
            'allow_negative' => fake()->boolean() ? 1 : 0,
            'max_continuous_days' => fake()->optional()->numberBetween(3, 30),
            'status' => fake()->randomElement([0, 1]),
        ];
    }
}
