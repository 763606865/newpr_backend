<?php

namespace Tests\Unit\Models\Rc;

use App\Filament\Support\NullableParentIdSelect;
use App\Models\Rc\Industry;
use App\Models\Rc\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NullableParentIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_nullable_parent_id_select_normalizes_zero_to_null(): void
    {
        $this->assertNull(NullableParentIdSelect::normalize(null));
        $this->assertNull(NullableParentIdSelect::normalize(0));
        $this->assertNull(NullableParentIdSelect::normalize('0'));
        $this->assertSame(3, NullableParentIdSelect::normalize(3));
    }

    public function test_zero_root_parent_id_select_dehydrates_empty_to_zero(): void
    {
        $this->assertSame(0, NullableParentIdSelect::dehydrateZeroRoot(null));
        $this->assertSame(0, NullableParentIdSelect::dehydrateZeroRoot(0));
        $this->assertSame(5, NullableParentIdSelect::dehydrateZeroRoot(5));
    }

    public function test_position_parent_id_stores_null_instead_of_zero(): void
    {
        $position = Position::query()->create([
            'name' => '后端开发',
            'code' => 'backend-developer',
            'parent_id' => 0,
            'sort' => 1,
        ]);

        $this->assertNull($position->parent_id);
        $this->assertNull($position->fresh()->getRawOriginal('parent_id'));
    }

    public function test_industry_parent_id_stores_null_instead_of_zero(): void
    {
        $industry = Industry::query()->create([
            'name' => '互联网',
            'code' => 'internet',
            'parent_id' => 0,
            'sort' => 1,
        ]);

        $this->assertNull($industry->parent_id);
        $this->assertNull($industry->fresh()->getRawOriginal('parent_id'));
    }
}
