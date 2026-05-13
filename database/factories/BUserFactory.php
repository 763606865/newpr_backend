<?php

namespace Database\Factories;

use App\Models\BUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BUserFactory extends Factory
{
    protected $model = BUser::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'nickname' => $this->faker->word(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'gender' => $this->faker->randomNumber(),
            'avatar' => $this->faker->word(),
            'password' => bcrypt($this->faker->password()),
            'status' => $this->faker->word(),
            'last_login_ip' => $this->faker->ipv4(),
            'last_login_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
