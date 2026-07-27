<?php

namespace Database\Factories\Rc;

use App\Enums\RcAssetDefinitionStatus;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetType;
use App\Models\Rc\AssetDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetDefinition>
 */
class AssetDefinitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_code' => fake()->unique()->lexify('asset_????????'),
            'asset_name' => fake()->words(3, true),
            'owner_type' => fake()->randomElement(RcAssetOwnerType::cases()),
            'asset_type' => RcAssetType::Count,
            'consume_scene' => fake()->optional()->lexify('scene_??????'),
            'unit' => '次',
            'default_duration' => fake()->numberBetween(0, 365),
            'description' => fake()->optional()->sentence(),
            'status' => RcAssetDefinitionStatus::Enabled,
            'sort' => fake()->numberBetween(0, 100),
            'extra' => null,
        ];
    }
}
