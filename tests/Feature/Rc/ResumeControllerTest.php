<?php

namespace Tests\Feature\Rc;

use App\Enums\UserGender;
use App\Models\Area;
use App\Models\Rc\Resume;
use App\Models\User;
use App\Services\MetaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_returns_only_current_user_resumes(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $primaryResume = $this->createResume($currentUser, [
            'title' => 'Primary Resume',
            'is_primary' => 1,
        ]);
        $this->createResume($currentUser, [
            'title' => 'Second Resume',
            'is_primary' => 0,
        ]);
        $this->createResume($otherUser, [
            'title' => 'Other User Resume',
            'is_primary' => 1,
        ]);

        $response = $this
            ->actingAs($currentUser, 'rc')
            ->getJson('/rc/resumes?page_size=10');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.data.0.id', $primaryResume->id);

        $this->assertSame('Second Resume', $response->json('data.data.1.title'));
    }

    public function test_show_returns_not_found_when_resume_does_not_belong_to_user(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherResume = $this->createResume($otherUser);

        $response = $this
            ->actingAs($currentUser, 'rc')
            ->getJson('/rc/resumes/'.$otherResume->id);

        $response
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '简历不存在。');
    }

    public function test_store_rejects_non_city_area_codes(): void
    {
        $user = User::factory()->create();
        $this->seedCityAreas();

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/resumes', [
                'full_name' => 'Area Tester',
                'phone' => '13800000088',
                'email' => 'area-test@example.com',
                'native_place' => '000001',
                'household_register' => '000001',
                'current_city_code' => '000001',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'native_place',
                'household_register',
                'current_city_code',
            ]);
    }

    public function test_store_accepts_city_level_area_codes(): void
    {
        $user = User::factory()->create();
        $this->seedCityAreas();

        config(['cache.default' => 'array']);
        app(MetaService::class)->forgetAreas();
        $this->seedJiangxiCityAreas();

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/resumes', [
                'title' => 'City Resume',
                'full_name' => 'City Tester',
                'phone' => '13800000087',
                'email' => 'city-test@example.com',
                'native_place' => '360100',
                'household_register' => '360100',
                'current_city_code' => '360100',
                'current_residence_city' => '前端传入应被忽略',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.native_place', '360100')
            ->assertJsonPath('data.household_register', '360100')
            ->assertJsonPath('data.current_city_code', '360100')
            ->assertJsonPath('data.current_residence_city', '中国江西省南昌市');
    }

    public function test_store_returns_dates_in_y_m_d_format(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/resumes', [
                'title' => 'Date Format Resume',
                'full_name' => 'Date Tester',
                'phone' => '13800000099',
                'email' => 'date-format@example.com',
                'birth_date' => '1998-05-20',
                'work_start_date' => '2020-07-01',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.birth_date', '1998-05-20')
            ->assertJsonPath('data.work_start_date', '2020-07-01');
    }

    public function test_store_creates_resume_and_sets_first_resume_as_primary(): void
    {
        $user = User::factory()->create();

        $payload = [
            'title' => 'New Resume',
            'full_name' => 'Test User',
            'avatar' => 'uploads/rc/avatar/2026/06/03/example.jpg',
            'phone' => '13800000000',
            'email' => 'resume@example.com',
            'is_primary' => 0,
        ];

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/resumes', $payload);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.title', 'New Resume')
            ->assertJsonPath('data.avatar', 'uploads/rc/avatar/2026/06/03/example.jpg')
            ->assertJsonPath('data.is_primary', 1)
            ->assertJsonPath('data.source_type', 3);

        $this->assertDatabaseHas('rc_resumes', [
            'user_id' => $user->id,
            'title' => 'New Resume',
            'avatar' => 'uploads/rc/avatar/2026/06/03/example.jpg',
            'is_primary' => 1,
        ]);
        $this->assertMatchesRegularExpression(
            '/^RC\d{14}[A-Z0-9]{6}$/',
            (string) $response->json('data.resume_no'),
        );
    }

    public function test_update_can_update_avatar(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user, [
            'avatar' => 'uploads/rc/avatar/2026/06/03/old.jpg',
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->putJson('/rc/resumes/'.$resume->id, [
                'avatar' => 'uploads/rc/avatar/2026/06/03/new.jpg',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.avatar', 'uploads/rc/avatar/2026/06/03/new.jpg');

        $this->assertDatabaseHas('rc_resumes', [
            'id' => $resume->id,
            'avatar' => 'uploads/rc/avatar/2026/06/03/new.jpg',
        ]);
    }

    public function test_store_primary_resume_fills_empty_user_profile(): void
    {
        $user = User::factory()->create([
            'name' => null,
            'nickname' => null,
            'avatar' => '',
            'gender' => UserGender::Unknown,
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/resumes', [
                'title' => 'Profile Resume',
                'full_name' => 'Zhang San',
                'avatar' => 'uploads/rc/avatar/2026/06/03/profile.jpg',
                'gender' => UserGender::Male->value,
                'phone' => '13800000001',
                'email' => 'profile@example.com',
            ]);

        $response->assertOk()->assertJsonPath('data.is_primary', 1);

        $user->refresh();

        $this->assertSame('Zhang San', $user->name);
        $this->assertSame('Zhang San', $user->nickname);
        $this->assertSame('uploads/rc/avatar/2026/06/03/profile.jpg', $user->avatar);
        $this->assertSame(UserGender::Male, $user->gender);
    }

    public function test_store_primary_resume_does_not_overwrite_existing_user_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Existing Name',
            'nickname' => 'Existing Nickname',
            'avatar' => 'uploads/rc/avatar/existing.jpg',
            'gender' => UserGender::Female,
        ]);

        $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/resumes', [
                'title' => 'Another Resume',
                'full_name' => 'New Name',
                'avatar' => 'uploads/rc/avatar/2026/06/03/new.jpg',
                'gender' => UserGender::Male->value,
                'phone' => '13800000002',
                'email' => 'another@example.com',
                'is_primary' => 1,
            ])
            ->assertOk();

        $user->refresh();

        $this->assertSame('Existing Name', $user->name);
        $this->assertSame('Existing Nickname', $user->nickname);
        $this->assertSame('uploads/rc/avatar/existing.jpg', $user->avatar);
        $this->assertSame(UserGender::Female, $user->gender);
    }

    public function test_update_can_switch_primary_resume(): void
    {
        $user = User::factory()->create();

        $oldPrimary = $this->createResume($user, [
            'title' => 'Old Primary',
            'is_primary' => 1,
        ]);
        $targetResume = $this->createResume($user, [
            'title' => 'Target Resume',
            'is_primary' => 0,
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->putJson('/rc/resumes/'.$targetResume->id, [
                'title' => 'Updated Resume',
                'is_primary' => 1,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.id', $targetResume->id)
            ->assertJsonPath('data.title', 'Updated Resume')
            ->assertJsonPath('data.is_primary', 1);

        $this->assertDatabaseHas('rc_resumes', [
            'id' => $targetResume->id,
            'is_primary' => 1,
            'title' => 'Updated Resume',
        ]);
        $this->assertDatabaseHas('rc_resumes', [
            'id' => $oldPrimary->id,
            'is_primary' => 0,
        ]);
    }

    public function test_update_switch_primary_resume_fills_empty_user_profile(): void
    {
        $user = User::factory()->create([
            'name' => null,
            'nickname' => null,
            'avatar' => '',
            'gender' => UserGender::Unknown,
        ]);

        $this->createResume($user, [
            'title' => 'Old Primary',
            'is_primary' => 1,
            'full_name' => 'Old Name',
        ]);
        $targetResume = $this->createResume($user, [
            'title' => 'Target Resume',
            'full_name' => 'Li Si',
            'avatar' => 'uploads/rc/avatar/2026/06/03/target.jpg',
            'gender' => UserGender::Female->value,
            'is_primary' => 0,
        ]);

        $this
            ->actingAs($user, 'rc')
            ->putJson('/rc/resumes/'.$targetResume->id, [
                'is_primary' => 1,
            ])
            ->assertOk();

        $user->refresh();

        $this->assertSame('Li Si', $user->name);
        $this->assertSame('Li Si', $user->nickname);
        $this->assertSame('uploads/rc/avatar/2026/06/03/target.jpg', $user->avatar);
        $this->assertSame(UserGender::Female, $user->gender);
    }

    private function seedCityAreas(): void
    {
        Area::query()->delete();

        Area::query()->insert([
            [
                'name' => 'Province A',
                'code' => '000001',
                'parent_code' => null,
                'level' => 1,
                'type' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'City A',
                'code' => '000001001',
                'parent_code' => '000001',
                'level' => 2,
                'type' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createResume(User $user, array $overrides = []): Resume
    {
        return Resume::query()->create(array_merge([
            'user_id' => $user->id,
            'resume_no' => 'RC-'.fake()->unique()->numerify('##########'),
            'title' => 'Test Resume',
            'full_name' => 'Tester',
            'phone' => fake()->unique()->numerify('1##########'),
            'email' => fake()->unique()->safeEmail(),
        ], $overrides));
    }
}
