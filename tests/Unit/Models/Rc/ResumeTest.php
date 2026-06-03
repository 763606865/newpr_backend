<?php

namespace Tests\Unit\Models\Rc;

use App\Models\Area;
use App\Models\Rc\Resume;
use App\Models\User;
use App\Services\MetaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ResumeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-03');
        config(['cache.default' => 'array']);
        app(MetaService::class)->forgetAreas();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_generates_resume_no_on_creating_when_missing(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => 'Auto Number Resume',
            'full_name' => 'Tester',
            'phone' => '13800000000',
            'email' => 'resume@example.com',
        ]);

        $this->assertMatchesRegularExpression('/^RC\d{14}[A-Z0-9]{6}$/', $resume->resume_no);
    }

    public function test_it_does_not_override_existing_resume_no_on_creating(): void
    {
        $user = User::factory()->create();
        $resumeNo = 'RC-CUSTOM-001';

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'resume_no' => $resumeNo,
            'title' => 'Custom Number Resume',
            'full_name' => 'Tester',
            'phone' => '13800000001',
            'email' => 'custom@example.com',
        ]);

        $this->assertSame($resumeNo, $resume->resume_no);
    }

    public function test_it_derives_age_and_birth_month_from_birth_date_on_create(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => 'Birth Date Resume',
            'full_name' => 'Tester',
            'phone' => '13800000002',
            'email' => 'birth@example.com',
            'birth_date' => '1998-05-20',
        ]);

        $this->assertSame('1998-05-20', $resume->birth_date);
        $this->assertSame(28, $resume->age);
        $this->assertSame('1998-05', $resume->birth_month);
    }

    public function test_it_derives_work_years_from_work_start_date_on_create(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => 'Work Start Resume',
            'full_name' => 'Tester',
            'phone' => '13800000003',
            'email' => 'work@example.com',
            'work_start_date' => '2020-07-01',
        ]);

        $this->assertSame('2020-07-01', $resume->work_start_date);
        $this->assertSame(5, $resume->work_years);
    }

    public function test_it_recalculates_age_when_birth_date_is_updated(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => 'Update Age Resume',
            'full_name' => 'Tester',
            'phone' => '13800000004',
            'email' => 'update-age@example.com',
            'birth_date' => '1998-05-20',
        ]);

        $resume->update([
            'birth_date' => '2000-01-15',
            'title' => 'Update Age Resume',
        ]);

        $resume->refresh();

        $this->assertSame(26, $resume->age);
        $this->assertSame('2000-01', $resume->birth_month);
    }

    public function test_it_syncs_current_residence_city_from_city_code_on_save(): void
    {
        $user = User::factory()->create();
        $this->seedJiangxiCityAreas();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => 'Residence City Resume',
            'full_name' => 'Tester',
            'phone' => '13800000006',
            'email' => 'residence@example.com',
            'current_city_code' => '360100',
        ]);

        $this->assertSame('中国江西省南昌市', $resume->current_residence_city);

        $resume->update(['current_city_code' => null]);

        $resume->refresh();

        $this->assertNull($resume->current_residence_city);
    }

    public function test_it_does_not_override_manual_age_when_birth_date_is_unchanged(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => 'Manual Age Resume',
            'full_name' => 'Tester',
            'phone' => '13800000005',
            'email' => 'manual-age@example.com',
            'birth_date' => '1998-05-20',
            'age' => 99,
        ]);

        $resume->update(['title' => 'Renamed Resume']);

        $resume->refresh();

        $this->assertSame(99, $resume->age);
    }

    private function seedJiangxiCityAreas(): void
    {
        Area::query()->delete();

        Area::query()->insert([
            [
                'name' => '江西省',
                'code' => '360000',
                'parent_code' => '000000',
                'level' => 1,
                'type' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '南昌市',
                'code' => '360100',
                'parent_code' => '360000',
                'level' => 2,
                'type' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
