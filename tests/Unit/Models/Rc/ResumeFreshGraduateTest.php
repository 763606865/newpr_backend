<?php

namespace Tests\Unit\Models\Rc;

use App\Enums\RcCurrentIdentity;
use App\Models\Rc\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeFreshGraduateTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_fresh_graduate_when_student_with_zero_work_years(): void
    {
        $this->assertTrue(Resume::resolvesFreshGraduate(RcCurrentIdentity::Student, 0));
    }

    public function test_does_not_resolve_fresh_graduate_when_student_has_work_years(): void
    {
        $this->assertFalse(Resume::resolvesFreshGraduate(RcCurrentIdentity::Student, 2));
    }

    public function test_does_not_resolve_fresh_graduate_for_non_student_identities(): void
    {
        $this->assertFalse(Resume::resolvesFreshGraduate(RcCurrentIdentity::WorkingPerson, 0));
    }

    public function test_saving_resume_syncs_is_fresh_graduate_from_identity_and_work_years(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => 'Fresh Resume',
            'full_name' => 'Fresh User',
            'phone' => '13800000000',
            'email' => 'fresh@example.com',
            'current_identity' => RcCurrentIdentity::Student,
            'work_years' => 0,
        ]);

        $this->assertSame(1, $resume->fresh()->is_fresh_graduate);

        $resume->update(['work_years' => 1]);

        $this->assertSame(0, $resume->fresh()->is_fresh_graduate);
    }
}
