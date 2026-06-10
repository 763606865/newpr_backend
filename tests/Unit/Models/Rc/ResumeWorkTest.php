<?php

namespace Tests\Unit\Models\Rc;

use App\Models\Rc\Position;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeWork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeWorkTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_position_name_from_position_code_on_save(): void
    {
        Position::query()->create([
            'name' => '后端开发',
            'code' => 'backend-developer',
            'sort' => 1,
        ]);

        Position::query()->create([
            'name' => '高级后端开发',
            'code' => 'senior-backend-developer',
            'sort' => 2,
        ]);

        $user = User::factory()->create();
        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'resume_no' => 'RC-TEST-001',
            'title' => 'Test Resume',
            'full_name' => 'Tester',
            'phone' => '13800138000',
            'email' => 'test@example.com',
        ]);

        $work = ResumeWork::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'company_name' => 'Acme Inc',
            'position_code' => 'backend-developer',
            'start_date' => '2022-01-01',
        ]);

        $this->assertSame('backend-developer', $work->position_code);
        $this->assertSame('后端开发', $work->position);

        $work->update(['position_code' => 'senior-backend-developer']);

        $this->assertSame('senior-backend-developer', $work->fresh()->position_code);
        $this->assertSame('高级后端开发', $work->fresh()->position);
    }
}
