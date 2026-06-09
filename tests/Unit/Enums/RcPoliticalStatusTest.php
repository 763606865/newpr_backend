<?php

namespace Tests\Unit\Enums;

use App\Enums\RcPoliticalStatus;
use PHPUnit\Framework\TestCase;

class RcPoliticalStatusTest extends TestCase
{
    public function test_labels_match_expected_values(): void
    {
        $this->assertSame('中共党员（含预备党员）', RcPoliticalStatus::CpcMember->getLabel());
        $this->assertSame('民主党派', RcPoliticalStatus::DemocraticParty->getLabel());
        $this->assertSame('无党派人士', RcPoliticalStatus::NonPartisan->getLabel());
        $this->assertSame('团员', RcPoliticalStatus::LeagueMember->getLabel());
        $this->assertSame('群众', RcPoliticalStatus::Masses->getLabel());
    }
}
