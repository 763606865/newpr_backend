<?php

namespace Tests\Unit\Discovery\Search;

use App\Discovery\Search\JobSearchIndex;
use Tests\TestCase;

class JobSearchIndexTest extends TestCase
{
    public function test_it_exposes_sortable_fields_and_mapping_for_is_urgent(): void
    {
        $this->assertContains('is_urgent', JobSearchIndex::scoutSortableFields());
        $this->assertSame(
            ['type' => 'byte'],
            JobSearchIndex::mappingProperties()['is_urgent'],
        );
        $this->assertStringEndsWith('rc_jobs', JobSearchIndex::indexName());
    }
}
