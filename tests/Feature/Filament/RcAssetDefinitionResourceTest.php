<?php

namespace Tests\Feature\Filament;

use App\Enums\RcAssetDefinitionStatus;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetType;
use App\Filament\Resources\Rc\AssetDefinitions\Pages\CreateAssetDefinition;
use App\Filament\Resources\Rc\AssetDefinitions\Pages\EditAssetDefinition;
use App\Models\Rc\AssetDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class RcAssetDefinitionResourceTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function assetDefinitionPermissions(): array
    {
        return [
            'ViewAny:AssetDefinition',
            'View:AssetDefinition',
            'Create:AssetDefinition',
            'Update:AssetDefinition',
        ];
    }

    public function test_admin_can_create_an_rc_asset_definition(): void
    {
        $this->actingAsFilamentAdmin($this->assetDefinitionPermissions());

        Livewire::test(CreateAssetDefinition::class)
            ->assertSuccessful()
            ->assertSee('权益名称')
            ->assertSee('消费场景')
            ->fillForm([
                'asset_name' => '职位紧急招聘',
                'asset_code' => 'job_urgent',
                'owner_type' => RcAssetOwnerType::Company,
                'asset_type' => RcAssetType::Count,
                'consume_scene' => 'job_urgent',
                'unit' => '次',
                'default_duration' => 7,
                'description' => '为企业名下职位开启紧急招聘展示。',
                'status' => RcAssetDefinitionStatus::Enabled,
                'sort' => 10,
                'extra' => [
                    'stackable' => false,
                ],
            ])
            ->call('create')
            ->assertNotified();

        $definition = AssetDefinition::query()->where('asset_code', 'job_urgent')->sole();

        $this->assertSame('职位紧急招聘', $definition->asset_name);
        $this->assertSame(RcAssetOwnerType::Company, $definition->owner_type);
        $this->assertSame(RcAssetType::Count, $definition->asset_type);
        $this->assertSame(7, $definition->default_duration);
        $this->assertSame(RcAssetDefinitionStatus::Enabled, $definition->status);
        $this->assertFalse($definition->extra['stackable']);
    }

    public function test_admin_can_update_and_disable_an_asset_definition_without_changing_its_code(): void
    {
        $this->actingAsFilamentAdmin($this->assetDefinitionPermissions());
        $definition = AssetDefinition::factory()->create([
            'asset_code' => 'job_urgent',
            'asset_name' => '职位紧急招聘',
            'status' => RcAssetDefinitionStatus::Enabled,
        ]);

        Livewire::test(EditAssetDefinition::class, ['record' => $definition->getRouteKey()])
            ->fillForm([
                'asset_name' => '职位紧急招聘权益',
                'asset_code' => 'changed_code',
                'status' => RcAssetDefinitionStatus::Disabled,
            ])
            ->call('save')
            ->assertNotified();

        $definition->refresh();

        $this->assertSame('job_urgent', $definition->asset_code);
        $this->assertSame('职位紧急招聘权益', $definition->asset_name);
        $this->assertSame(RcAssetDefinitionStatus::Disabled, $definition->status);
    }

    public function test_asset_code_must_be_unique(): void
    {
        $this->actingAsFilamentAdmin($this->assetDefinitionPermissions());
        AssetDefinition::factory()->create(['asset_code' => 'job_urgent']);

        Livewire::test(CreateAssetDefinition::class)
            ->fillForm([
                'asset_name' => '重复权益',
                'asset_code' => 'job_urgent',
                'owner_type' => RcAssetOwnerType::Company,
                'asset_type' => RcAssetType::Count,
                'unit' => '次',
                'default_duration' => 7,
                'status' => RcAssetDefinitionStatus::Enabled,
                'sort' => 20,
            ])
            ->call('create')
            ->assertHasFormErrors(['asset_code' => 'unique']);

        $this->assertSame(1, AssetDefinition::query()->count());
    }
}
