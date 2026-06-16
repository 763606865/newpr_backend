<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(RoleAndAdminSeeder::class);
        $this->call(PassportPersonalAccessClientSeeder::class);
        $this->call(DefaultFeaturesAndPlansSeeder::class);
        $this->call(OaLeaveSeeder::class);
        $this->call(BasicTableSeeder::class);
        $this->call(RcIndustrySeeder::class);
        $this->call(RcPositionSeeder::class);
        $this->call(MajorSeeder::class);
        $this->call(InitCmsTagSeeder::class);
    }
}
