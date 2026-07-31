<?php

namespace Database\Factories;

use App\Enums\RcContactInquiryStatus;
use App\Enums\RcContactProduct;
use App\Models\AdminUser;
use App\Models\ContactInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactInquiry>
 */
class ContactInquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->numerify('1##########'),
            'company_name' => fake()->optional()->company(),
            'source' => fake()->optional()->randomElement(['website', 'mini_program', 'app']),
            'product' => fake()->randomElement(RcContactProduct::cases()),
            'content' => fake()->paragraph(),
            'status' => RcContactInquiryStatus::Pending,
            'submitted_at' => now(),
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'extra' => null,
        ];
    }

    /**
     * 标记为已由运营人员回访。
     */
    public function followedUp(AdminUser $admin): static
    {
        return $this->state(fn (): array => [
            'status' => RcContactInquiryStatus::FollowedUp,
            'follow_up_admin_user_id' => $admin->id,
            'follow_up_note' => fake()->sentence(),
            'followed_up_at' => now(),
        ]);
    }
}
