<?php

namespace Tests\Unit\Models\Rc;

use App\Enums\RcIdentityType;
use App\Models\Rc\UserIdentity;
use Tests\TestCase;

class UserIdentityTest extends TestCase
{
    public function test_headhunter_identity_always_has_basic_info(): void
    {
        $identity = new UserIdentity;
        $identity->forceFill([
            'identity_type' => RcIdentityType::Headhunter,
        ]);

        $this->assertTrue($identity->has_basic_info);
    }

    public function test_recruiter_identity_requires_company_binding(): void
    {
        $identity = new UserIdentity;
        $identity->forceFill([
            'identity_type' => RcIdentityType::Recruiter,
            'company_id' => null,
        ]);

        $this->assertFalse($identity->has_basic_info);

        $identity->forceFill([
            'company_id' => 10,
        ]);

        $this->assertTrue($identity->has_basic_info);
    }

    public function test_campus_manager_identity_requires_school_binding(): void
    {
        $identity = new UserIdentity;
        $identity->forceFill([
            'identity_type' => RcIdentityType::CampusManager,
            'extra' => [],
        ]);

        $this->assertFalse($identity->has_basic_info);

        $identity->forceFill([
            'extra' => ['school_code' => '10001'],
        ]);

        $this->assertTrue($identity->has_basic_info);
    }

    public function test_government_manager_identity_requires_area_binding(): void
    {
        $identity = new UserIdentity;
        $identity->forceFill([
            'identity_type' => RcIdentityType::GovernmentManager,
            'extra' => [],
        ]);

        $this->assertFalse($identity->has_basic_info);

        $identity->forceFill([
            'extra' => ['city_code' => '440100'],
        ]);

        $this->assertTrue($identity->has_basic_info);
    }

    public function test_job_seeker_identity_supports_preloaded_resume_flag(): void
    {
        $identity = new UserIdentity;
        $identity->forceFill([
            'identity_type' => RcIdentityType::JobSeeker,
            'jobseeker_has_resume' => 0,
        ]);

        $this->assertFalse($identity->has_basic_info);

        $identity->forceFill([
            'jobseeker_has_resume' => 1,
        ]);

        $this->assertTrue($identity->has_basic_info);
    }

    public function test_job_seeker_identity_supports_preloaded_basic_info_cache_flag(): void
    {
        $identity = new UserIdentity;
        $identity->forceFill([
            'identity_type' => RcIdentityType::JobSeeker,
            'has_basic_info_cached' => 0,
        ]);

        $this->assertFalse($identity->has_basic_info);

        $identity->forceFill([
            'has_basic_info_cached' => 1,
        ]);

        $this->assertTrue($identity->has_basic_info);
    }

    public function test_with_basic_info_flags_scope_adds_resume_exists_subquery(): void
    {
        $query = UserIdentity::query()->withBasicInfoFlags();
        $sql = $query->toSql();

        $this->assertStringContainsString('jobseeker_has_resume', $sql);
        $this->assertStringContainsString('has_basic_info_cached', $sql);
        $this->assertStringContainsString('from rc_resumes', $sql);
    }
}
