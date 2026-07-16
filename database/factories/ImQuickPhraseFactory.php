<?php

namespace Database\Factories;

use App\Models\ImQuickPhrase;
use App\Models\Rc\UserIm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImQuickPhrase>
 */
class ImQuickPhraseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_im_id' => UserIm::query()->inRandomOrder()->value('id') ?? 1,
            'title' => fake()->randomElement(['打招呼', '职位咨询', '面试邀约', '感谢回复']),
            'content' => fake()->randomElement([
                '您好，我想进一步了解一下这个职位。',
                '您好，方便现在沟通一下吗？',
                '感谢您的回复，我会尽快查看相关信息。',
                '您好，想和您确认一下后续面试安排。',
            ]),
            'sort' => fake()->numberBetween(0, 100),
            'is_enabled' => true,
            'used_count' => fake()->numberBetween(0, 20),
            'last_used_at' => fake()->optional()->dateTimeBetween('-1 month'),
        ];
    }
}
