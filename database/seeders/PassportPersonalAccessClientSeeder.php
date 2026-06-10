<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Client;

class PassportPersonalAccessClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete any existing personal access client to ensure clean state
        DB::table('oauth_clients')
            ->where('name', '=', '牛派C端')
            ->delete();

        DB::table('oauth_clients')->insert([
            'id' => Str::uuid(),
            'name' => '牛派C端',
            'secret' => Str::random(40),
            'provider' => 'users',
            'redirect_uris' => '',
            'grant_types' => json_encode(['personal_access']),
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('oauth_clients')
            ->where('name', '=', '牛派B端')
            ->delete();

        DB::table('oauth_clients')->insert([
            'id' => Str::uuid(),
            'name' => '牛派B端',
            'secret' => Str::random(40),
            'provider' => 'b_users',
            'redirect_uris' => '',
            'grant_types' => json_encode(['personal_access']),
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('oauth_clients')
            ->where('name', '=', '招聘C端')
            ->delete();

        DB::table('oauth_clients')->insert([
            'id' => Str::uuid(),
            'name' => '招聘C端',
            'secret' => Str::random(40),
            'provider' => 'rc_users',
            'redirect_uris' => '',
            'grant_types' => json_encode(['personal_access']),
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
