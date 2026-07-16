<?php

namespace Database\Seeders;

use App\Models\ImQuickPhrase;
use App\Models\Rc\UserIm;
use Illuminate\Database\Seeder;

class ImQuickPhraseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIm = UserIm::query()->first();

        if (! $userIm instanceof UserIm) {
            return;
        }

        ImQuickPhrase::factory()
            ->count(5)
            ->create([
                'user_im_id' => $userIm->id,
            ]);
    }
}
