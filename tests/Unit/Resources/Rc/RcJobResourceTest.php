<?php

namespace Tests\Unit\Resources\Rc;

use App\Enums\UserGender;
use App\Models\Company;
use App\Models\CompanyProfile;
use App\Models\Rc\Job;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Resources\Rc\RcJobResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class RcJobResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_serializes_company_profile_with_display_logo_when_eager_loaded(): void
    {
        $disk = Mockery::mock();
        $disk->shouldReceive('url')
            ->once()
            ->with('uploads/company/logo.png')
            ->andReturn('https://cdn.example.com/uploads/company/logo.png');

        Storage::shouldReceive('disk')
            ->once()
            ->with('oss')
            ->andReturn($disk);

        $company = new Company([
            'id' => 10,
            'name' => '南昌示例科技有限公司',
        ]);
        $company->setRelation('profile', new CompanyProfile([
            'short_name' => '示例科技',
            'logo' => 'uploads/company/logo.png',
        ]));

        $job = new Job([
            'id' => 1,
            'title' => 'Laravel 工程师',
        ]);
        $job->setRelation('company', $company);

        $payload = (new RcJobResource($job))->resolve(new Request);

        $this->assertSame('南昌示例科技有限公司', $payload['company']['name']);
        $this->assertSame('示例科技', $payload['company']['profile']['short_name']);
        $this->assertSame('uploads/company/logo.png', $payload['company']['profile']['logo']);
        $this->assertSame('https://cdn.example.com/uploads/company/logo.png', $payload['company']['profile']['display_logo']);
    }

    public function test_it_omits_company_profile_when_relation_is_not_eager_loaded(): void
    {
        $company = new Company([
            'id' => 10,
            'name' => '南昌示例科技有限公司',
        ]);

        $job = new Job([
            'id' => 1,
            'title' => 'Laravel 工程师',
        ]);
        $job->setRelation('company', $company);

        $payload = (new RcJobResource($job))->resolve(new Request);

        $this->assertSame('南昌示例科技有限公司', $payload['company']['name']);
        $this->assertArrayNotHasKey('profile', $payload['company']);
    }

    public function test_it_serializes_creator_with_display_avatar_and_job_title_when_eager_loaded(): void
    {
        $disk = Mockery::mock();
        $disk->shouldReceive('url')
            ->once()
            ->with('uploads/users/avatar/publisher.jpg')
            ->andReturn('https://cdn.example.com/uploads/users/avatar/publisher.jpg');

        Storage::shouldReceive('disk')
            ->once()
            ->with('oss')
            ->andReturn($disk);

        $creator = User::factory()->create([
            'name' => '张招聘',
            'gender' => UserGender::Unknown,
            'avatar' => 'uploads/users/avatar/publisher.jpg',
        ]);
        $creator->setRelation('recruiterCompanyIdentities', collect([
            new UserIdentity([
                'organization_id' => 10,
                'job_title' => 'HR 经理',
            ]),
        ]));

        $job = new Job([
            'id' => 1,
            'title' => 'Laravel 工程师',
            'company_id' => 10,
            'creator_user_id' => $creator->id,
        ]);
        $job->setRelation('creator', $creator);

        $payload = (new RcJobResource($job))->resolve(new Request);

        $this->assertSame($creator->id, $payload['creator']['id']);
        $this->assertSame('张总', $payload['creator']['mask_name']);
        $this->assertSame('https://cdn.example.com/uploads/users/avatar/publisher.jpg', $payload['creator']['display_avatar']);
        $this->assertSame('HR 经理', $payload['creator']['job_title']);
    }

    public function test_it_omits_creator_job_title_when_identity_is_not_eager_loaded(): void
    {
        $creator = new User([
            'name' => '张招聘',
        ]);
        $creator->id = 5;

        $job = new Job([
            'id' => 1,
            'title' => 'Laravel 工程师',
        ]);
        $job->setRelation('creator', $creator);

        $payload = (new RcJobResource($job))->resolve(new Request);

        $this->assertSame('张总', $payload['creator']['mask_name']);
        $this->assertArrayNotHasKey('job_title', $payload['creator']);
    }
}
