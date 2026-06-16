<?php

namespace Tests\Unit;

use App\Enums\AreaLevel;
use App\Models\Area;
use Tests\TestCase;

class AreaSchoolLevelEnumTest extends TestCase
{
    public function test_area_level_uses_enum_cast(): void
    {
        $area = new Area;
        $area->level = AreaLevel::City;

        $this->assertInstanceOf(AreaLevel::class, $area->level);
        $this->assertSame(AreaLevel::City, $area->level);
        $this->assertSame(AreaLevel::City->value, $area->getAttributes()['level']);
    }
}
