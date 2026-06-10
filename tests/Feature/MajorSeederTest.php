<?php

namespace Tests\Feature;

use App\Enums\MajorEducationType;
use App\Models\Major;
use App\Services\MetaService;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MajorSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        app(MetaService::class)->forgetAll();
    }

    public function test_it_seeds_undergraduate_majors_from_official_catalog(): void
    {
        Major::query()->create([
            'full_code' => '55',
            'name' => '装备制造大类',
            'level' => 1,
            'parent_code' => null,
            'type' => MajorEducationType::VocationalSecondary->value,
            'sort' => 1,
        ]);

        $this->seed(MajorSeeder::class);

        $this->assertSame(980, Major::query()->where('type', MajorEducationType::Undergraduate->value)->count());
        $this->assertSame(1, Major::query()->where('type', MajorEducationType::VocationalSecondary->value)->count());

        $philosophy = Major::query()->where('full_code', '010101')->first();
        $this->assertNotNull($philosophy);
        $this->assertSame('哲学', $philosophy->name);
        $this->assertSame('0101', $philosophy->parent_code);
        $this->assertSame('', $philosophy->tag);

        $specialMajor = Major::query()->where('full_code', '010103K')->first();
        $this->assertNotNull($specialMajor);
        $this->assertSame('宗教学', $specialMajor->name);
        $this->assertSame('K', $specialMajor->tag);

        $crossMajor = Major::query()->where('full_code', '140015T')->first();
        $this->assertNotNull($crossMajor);
        $this->assertSame('深地科学与工程', $crossMajor->name);
        $this->assertSame('14', $crossMajor->parent_code);
    }

    public function test_seeded_majors_are_available_in_meta_tree(): void
    {
        $this->seed(MajorSeeder::class);

        $tree = app(MetaService::class)->getMajorsTree();

        $this->assertNotEmpty($tree);
        $this->assertSame('哲学', collect($tree)->firstWhere('full_code', '01')['name']);
        $this->assertSame(
            '哲学',
            collect($tree)->firstWhere('full_code', '01')['children'][0]['children'][0]['name'],
        );
    }

    public function test_seeder_is_idempotent_for_undergraduate_records(): void
    {
        $this->seed(MajorSeeder::class);
        $this->seed(MajorSeeder::class);

        $this->assertSame(980, Major::query()->where('type', MajorEducationType::Undergraduate->value)->count());
    }
}
