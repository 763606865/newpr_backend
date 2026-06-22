<?php

namespace Tests\Unit;

use App\Enums\RcSchoolActivityOrganizerType;
use App\Models\Rc\SchoolActivity;
use Tests\TestCase;

class RcSchoolActivityOrganizerTypeTest extends TestCase
{
    public function test_organizer_type_enum_values_match_morph_map(): void
    {
        $this->assertSame('school', RcSchoolActivityOrganizerType::School->value);
        $this->assertSame('company', RcSchoolActivityOrganizerType::Company->value);
        $this->assertSame('area', RcSchoolActivityOrganizerType::Area->value);
    }

    public function test_school_activity_uses_organizer_type_enum_cast(): void
    {
        $activity = new SchoolActivity;
        $activity->organizer_type = RcSchoolActivityOrganizerType::School;

        $this->assertInstanceOf(RcSchoolActivityOrganizerType::class, $activity->organizer_type);
        $this->assertSame(RcSchoolActivityOrganizerType::School, $activity->organizer_type);
        $this->assertSame('school', $activity->getAttributes()['organizer_type']);
    }
}
