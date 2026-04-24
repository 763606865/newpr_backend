<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->name(),
            'nickname' => fake()->optional()->firstName(),
            'phone' => fake()->unique()->numerify('1##########'),
            'email' => fake()->safeEmail(),
            'gender' => fake()->randomElement([0, 1, 2]),
            'password' => static::$password ??= Hash::make('password'),
            'status' => fake()->randomElement(['active', 'inactive', 'disabled']),
            'last_login_ip' => fake()->optional()->ipv4(),
            'last_login_at' => fake()->optional()->dateTimeBetween('-1 month'),
        ];
    }
}
