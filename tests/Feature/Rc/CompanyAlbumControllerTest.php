<?php

namespace Tests\Feature\Rc;

use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Company;
use App\Models\Rc\CompanyAlbum;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyAlbumControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_requires_recruiter_company(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'rc')
            ->getJson('/rc/companies/albums')
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为招聘方身份并绑定企业。');
    }

    public function test_index_returns_current_company_albums(): void
    {
        [$user, $company] = $this->createRecruiterContext();
        [, $otherCompany] = $this->createRecruiterContext();

        CompanyAlbum::query()->create([
            'company_id' => $company->id,
            'title' => '办公环境',
            'image' => 'uploads/rc/company-albums/office.jpg',
            'description' => '南昌总部办公室',
            'type' => 1,
            'sort' => 10,
            'status' => 1,
        ]);
        CompanyAlbum::query()->create([
            'company_id' => $otherCompany->id,
            'title' => '其他企业图片',
            'image' => 'uploads/rc/company-albums/other.jpg',
            'type' => 1,
            'status' => 1,
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/companies/albums?keyword='.urlencode('办公').'&type=1&status=1')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.company_id', $company->id)
            ->assertJsonPath('data.data.0.title', '办公环境')
            ->assertJsonPath('data.data.0.image', 'uploads/rc/company-albums/office.jpg')
            ->assertJsonPath('data.data.0.type_label', '办公环境')
            ->assertJsonPath('data.data.0.status_label', '启用');
    }

    public function test_recruiter_can_create_show_update_and_delete_company_album(): void
    {
        [$user, $company] = $this->createRecruiterContext();

        $createResponse = $this->actingAs($user, 'rc')
            ->postJson('/rc/companies/albums', [
                'title' => '企业文化',
                'image' => 'uploads/rc/company-albums/culture.jpg',
                'description' => '团队活动照片',
                'type' => 2,
                'sort' => 20,
                'status' => 1,
                'extra' => [
                    'scene' => 'team_building',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.album.company_id', $company->id)
            ->assertJsonPath('data.album.title', '企业文化')
            ->assertJsonPath('data.album.image', 'uploads/rc/company-albums/culture.jpg')
            ->assertJsonPath('data.album.type_label', '企业文化相册');

        $albumId = (int) $createResponse->json('data.album.id');

        $this->assertDatabaseHas('rc_company_albums', [
            'id' => $albumId,
            'company_id' => $company->id,
            'title' => '企业文化',
            'image' => 'uploads/rc/company-albums/culture.jpg',
            'type' => 2,
            'sort' => 20,
            'status' => 1,
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/companies/albums/'.$albumId)
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.album.id', $albumId);

        $this->actingAs($user, 'rc')
            ->patchJson('/rc/companies/albums/'.$albumId, [
                'title' => '企业荣誉',
                'type' => 3,
                'status' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.album.title', '企业荣誉')
            ->assertJsonPath('data.album.type_label', '企业荣誉相册')
            ->assertJsonPath('data.album.status_label', '停用');

        $this->assertDatabaseHas('rc_company_albums', [
            'id' => $albumId,
            'title' => '企业荣誉',
            'type' => 3,
            'status' => 0,
        ]);

        $this->actingAs($user, 'rc')
            ->deleteJson('/rc/companies/albums/'.$albumId)
            ->assertOk()
            ->assertJsonPath('code', 200);

        $this->assertSoftDeleted('rc_company_albums', [
            'id' => $albumId,
        ]);
    }

    public function test_recruiter_cannot_access_other_company_album(): void
    {
        [$user] = $this->createRecruiterContext();
        [, $otherCompany] = $this->createRecruiterContext();

        $album = CompanyAlbum::query()->create([
            'company_id' => $otherCompany->id,
            'title' => '其他企业图片',
            'image' => 'uploads/rc/company-albums/other.jpg',
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/companies/albums/'.$album->id)
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '企业相册不存在。');

        $this->actingAs($user, 'rc')
            ->deleteJson('/rc/companies/albums/'.$album->id)
            ->assertOk()
            ->assertJsonPath('code', 404);
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function createRecruiterContext(): array
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA'.strtoupper(substr(uniqid(), -8)),
            'status' => CompanyStatus::Enabled,
        ]);

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'organization_name' => $company->name,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => '招聘方',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        return [$user, $company];
    }
}
