<?php

namespace Tests\Unit\Models\Rc;

use App\Enums\RcPoliticalStatus;
use App\Models\Rc\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumePoliticalStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_resume_defaults_political_status_to_masses(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => 'Default Political Resume',
            'full_name' => 'Tester',
            'phone' => '13800000000',
            'email' => 'tester@example.com',
        ]);

        $this->assertSame(RcPoliticalStatus::Masses, $resume->political_status);
        $this->assertDatabaseHas('rc_resumes', [
            'id' => $resume->id,
            'political_status' => RcPoliticalStatus::Masses->value,
        ]);
    }
}
