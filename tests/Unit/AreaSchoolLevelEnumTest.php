<?php

namespace Tests\Unit;

use App\Enums\AreaLevel;
use App\Models\Area;
use App\Models\School;
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

    public function test_school_at_level_scope_accepts_enum(): void
    {
        $query = School::query()->atLevel(AreaLevel::District);

        $this->assertStringContainsString('"schools"."level" = ?', $query->toSql());
        $this->assertSame([AreaLevel::District->value], $query->getBindings());
    }
}
