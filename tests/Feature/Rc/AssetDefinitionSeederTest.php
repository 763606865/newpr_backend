<?php

namespace Tests\Feature\Rc;

use App\Enums\RcAssetDefinitionStatus;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetType;
use App\Models\Rc\AssetDefinition;
use Database\Seeders\AssetDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetDefinitionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_common_recruitment_asset_definitions(): void
    {
        $this->seed(AssetDefinitionSeeder::class);

        $this->assertSame(16, AssetDefinition::query()->count());
        $this->assertEqualsCanonicalizing([
            'ai_job_description',
            'ai_resume_optimization',
            'candidate_contact',
            'candidate_vip_basic',
            'candidate_vip_premium',
            'career_coaching',
            'job_posting',
            'job_posting_campus',
            'job_posting_full_time',
            'job_refresh',
            'job_top',
            'job_urgent',
            'resume_download',
            'resume_exposure',
            'resume_optimization',
            'resume_refresh',
        ], AssetDefinition::query()->pluck('asset_code')->all());

        $jobUrgent = AssetDefinition::query()->where('asset_code', 'job_urgent')->sole();
        $this->assertSame(RcAssetOwnerType::Company, $jobUrgent->owner_type);
        $this->assertSame(RcAssetType::Count, $jobUrgent->asset_type);
        $this->assertSame(RcAssetDefinitionStatus::Enabled, $jobUrgent->status);
        $this->assertSame(7, $jobUrgent->extra['effect_duration_days']);

        $premiumVip = AssetDefinition::query()->where('asset_code', 'candidate_vip_premium')->sole();
        $this->assertSame(RcAssetOwnerType::User, $premiumVip->owner_type);
        $this->assertSame(RcAssetType::Membership, $premiumVip->asset_type);
        $this->assertSame(2, $premiumVip->extra['vip_level']);
    }

    public function test_seeder_is_idempotent_and_restores_builtin_definitions(): void
    {
        $this->seed(AssetDefinitionSeeder::class);
        $definition = AssetDefinition::query()->where('asset_code', 'resume_refresh')->sole();
        $originalId = $definition->id;
        $definition->update(['asset_name' => '临时名称']);

        $this->seed(AssetDefinitionSeeder::class);

        $definition->refresh();

        $this->assertSame(16, AssetDefinition::query()->count());
        $this->assertSame($originalId, $definition->id);
        $this->assertSame('简历刷新', $definition->asset_name);
    }
}
