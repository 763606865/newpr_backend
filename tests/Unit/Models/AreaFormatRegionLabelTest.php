<?php

namespace Tests\Unit\Models;

use App\Models\Area;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaFormatRegionLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_formats_region_label_from_province_city_and_district_codes(): void
    {
        Area::query()->create([
            'name' => '江西省',
            'code' => '360000',
            'parent_code' => '000000',
            'level' => 1,
            'type' => null,
        ]);

        Area::query()->create([
            'name' => '南昌市',
            'code' => '360100',
            'parent_code' => '360000',
            'level' => 2,
            'type' => null,
        ]);

        Area::query()->create([
            'name' => '西湖区',
            'code' => '360103',
            'parent_code' => '360100',
            'level' => 3,
            'type' => null,
        ]);

        $this->assertSame(
            '江西省-南昌市-西湖区',
            Area::formatRegionLabel('360000', '360100', '360103'),
        );
    }

    public function test_it_returns_null_when_all_region_codes_are_blank(): void
    {
        $this->assertNull(Area::formatRegionLabel(null, null, null));
    }
}
