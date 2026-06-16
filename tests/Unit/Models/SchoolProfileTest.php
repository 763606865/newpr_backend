<?php

namespace Tests\Unit\Models;

use App\Enums\SchoolProfileStatus;
use App\Models\School;
use App\Models\SchoolProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_and_profile_are_linked_by_school_code(): void
    {
        $school = School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
            'province' => '北京市',
            'city' => '北京市',
            'type' => '本科',
        ]);

        $profile = SchoolProfile::query()->create([
            'school_code' => '4111010001',
            'short_name' => '北大',
            'intro' => '院校简介',
            'status' => SchoolProfileStatus::Normal,
        ]);

        $school->load('profile');
        $profile->load('school');

        $this->assertTrue($school->profile->is($profile));
        $this->assertTrue($profile->school->is($school));
        $this->assertSame('4111010001', $school->profile?->school_code);
        $this->assertSame('北京大学', $profile->school?->name);
    }
}
