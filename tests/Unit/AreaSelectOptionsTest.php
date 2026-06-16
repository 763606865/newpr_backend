<?php

namespace Tests\Unit;

use App\Enums\AreaLevel;
use App\Models\Area;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaSelectOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_province_options_only_include_level_one_areas(): void
    {
        Area::query()->create([
            'code' => '000000',
            'name' => '全国',
            'parent_code' => null,
            'level' => AreaLevel::Country,
        ]);
        Area::query()->create([
            'code' => '110000',
            'name' => '北京市',
            'parent_code' => '000000',
            'level' => AreaLevel::Province,
        ]);
        Area::query()->create([
            'code' => '110101',
            'name' => '东城区',
            'parent_code' => '110000',
            'level' => AreaLevel::District,
        ]);

        $options = Area::provinceOptions();

        $this->assertSame(['110000' => '北京市'], $options);
    }

    public function test_city_and_district_options_cascade_by_parent_code(): void
    {
        Area::query()->create([
            'code' => '360000',
            'name' => '江西省',
            'parent_code' => '000000',
            'level' => AreaLevel::Province,
        ]);
        Area::query()->create([
            'code' => '360100',
            'name' => '南昌市',
            'parent_code' => '360000',
            'level' => AreaLevel::City,
        ]);
        Area::query()->create([
            'code' => '360102',
            'name' => '东湖区',
            'parent_code' => '360100',
            'level' => AreaLevel::District,
        ]);

        $this->assertSame(['360100' => '南昌市'], Area::cityOptions('360000'));
        $this->assertSame(['360102' => '东湖区'], Area::districtOptions('360000', '360100'));
    }

    public function test_district_options_fall_back_to_province_when_city_is_missing(): void
    {
        Area::query()->create([
            'code' => '110000',
            'name' => '北京市',
            'parent_code' => '000000',
            'level' => AreaLevel::Province,
        ]);
        Area::query()->create([
            'code' => '110101',
            'name' => '东城区',
            'parent_code' => '110000',
            'level' => AreaLevel::District,
        ]);

        $this->assertSame(['110101' => '东城区'], Area::districtOptions('110000', null));
    }

    public function test_resolve_announcement_area_code_uses_most_specific_selection(): void
    {
        $this->assertSame('360102', Area::resolveAnnouncementAreaCode('360000', '360100', '360102'));
        $this->assertSame('360100', Area::resolveAnnouncementAreaCode('360000', '360100', null));
        $this->assertSame('360000', Area::resolveAnnouncementAreaCode('360000', null, null));
        $this->assertNull(Area::resolveAnnouncementAreaCode(null, null, null));
    }

    public function test_resolve_area_hierarchy_from_city_and_district_codes(): void
    {
        Area::query()->create([
            'code' => '360000',
            'name' => '江西省',
            'parent_code' => '000000',
            'level' => AreaLevel::Province,
        ]);
        Area::query()->create([
            'code' => '360100',
            'name' => '南昌市',
            'parent_code' => '360000',
            'level' => AreaLevel::City,
        ]);
        Area::query()->create([
            'code' => '360102',
            'name' => '东湖区',
            'parent_code' => '360100',
            'level' => AreaLevel::District,
        ]);
        Area::query()->create([
            'code' => '110000',
            'name' => '北京市',
            'parent_code' => '000000',
            'level' => AreaLevel::Province,
        ]);
        Area::query()->create([
            'code' => '110101',
            'name' => '东城区',
            'parent_code' => '110000',
            'level' => AreaLevel::District,
        ]);

        $this->assertSame([
            'province_code' => '360000',
            'city_code' => '360100',
            'district_code' => '360102',
        ], Area::resolveAreaHierarchy('360102'));

        $this->assertSame([
            'province_code' => '110000',
            'city_code' => null,
            'district_code' => '110101',
        ], Area::resolveAreaHierarchy('110101'));
    }

    public function test_resolve_area_hierarchy_for_city_code_only(): void
    {
        Area::query()->create([
            'code' => '360000',
            'name' => '江西省',
            'parent_code' => '000000',
            'level' => AreaLevel::Province,
        ]);
        Area::query()->create([
            'code' => '360100',
            'name' => '南昌市',
            'parent_code' => '360000',
            'level' => AreaLevel::City,
        ]);

        $this->assertSame([
            'province_code' => '360000',
            'city_code' => '360100',
            'district_code' => null,
        ], Area::resolveAreaHierarchy('360100'));
    }
}
