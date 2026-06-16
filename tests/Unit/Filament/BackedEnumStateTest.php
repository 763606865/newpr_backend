<?php

namespace Tests\Unit\Filament;

use App\Enums\RcJobStatus;
use App\Enums\RcResumeStatus;
use App\Filament\Support\BackedEnumState;
use Tests\TestCase;

class BackedEnumStateTest extends TestCase
{
    public function test_resolve_returns_enum_instance_when_state_is_already_enum(): void
    {
        $this->assertSame(
            RcJobStatus::Published,
            BackedEnumState::resolve(RcJobStatus::class, RcJobStatus::Published),
        );
    }

    public function test_resolve_returns_enum_from_backed_value(): void
    {
        $this->assertSame(
            RcResumeStatus::Normal,
            BackedEnumState::resolve(RcResumeStatus::class, 1),
        );
    }

    public function test_label_returns_enum_label_for_casted_state(): void
    {
        $this->assertSame(
            '已发布',
            BackedEnumState::label(RcJobStatus::class, RcJobStatus::Published),
        );
    }

    public function test_label_returns_placeholder_for_unknown_state(): void
    {
        $this->assertSame('-', BackedEnumState::label(RcJobStatus::class, 99));
        $this->assertSame('-', BackedEnumState::label(RcJobStatus::class, null));
    }
}
