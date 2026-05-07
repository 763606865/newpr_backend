<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PassportPersonalAccessClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete any existing personal access client to ensure clean state
        DB::table('oauth_clients')
            ->where('name', 'Laravel Personal Access Client')
            ->delete();

        DB::table('oauth_clients')->insert([
            'id' => Str::uuid(),
            'name' => 'Laravel Personal Access Client',
            'secret' => Str::random(40),
            'provider' => 'users',
            'redirect_uris' => '',
            'grant_types' => json_encode(['personal_access']),
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
