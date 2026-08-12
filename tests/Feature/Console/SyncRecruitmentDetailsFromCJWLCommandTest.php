<?php

namespace Tests\Feature\Console;

use App\Jobs\Rc\SyncRecruitmentDetailsFromCJWLJob;
use App\Libs\Facades\CJWL;
use App\Libs\ThirdParty\CJWL\Api\RecruitmentDetail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SyncRecruitmentDetailsFromCJWLCommandTest extends TestCase
{
    public function test_it_fetches_each_page_without_requesting_a_page_after_the_last_page(): void
    {
        Queue::fake();
        $api = Mockery::mock(RecruitmentDetail::class);
        $api->shouldReceive('query')->once()->ordered()->with([
            'page' => 1,
        ])->andReturn($this->response(page: 1, total: 201, totalPages: 3));
        $api->shouldReceive('query')->once()->ordered()->with([
            'page' => 2,
        ])->andReturn($this->response(page: 2, total: 201, totalPages: 3));
        $api->shouldReceive('query')->once()->ordered()->with([
            'page' => 3,
        ])->andReturn($this->response(page: 3, total: 201, totalPages: 3));

        CJWL::shouldReceive('recruitmentDetail')->times(3)->andReturn($api);

        $this->artisan('rc:recruitment-details:sync-from:cjwl')
            ->expectsOutput('共找到 201 条招考公告，每页 1 条分发到队列')
            ->assertExitCode(0);

        Queue::assertPushed(SyncRecruitmentDetailsFromCJWLJob::class, 3);
    }

    public function test_it_stops_after_the_first_request_when_there_are_no_records(): void
    {
        Queue::fake();
        $api = Mockery::mock(RecruitmentDetail::class);
        $api->shouldReceive('query')->once()->with([
            'page' => 1,
        ])->andReturn($this->response(page: 1, total: 0, totalPages: 0));

        CJWL::shouldReceive('recruitmentDetail')->once()->andReturn($api);

        $this->artisan('rc:recruitment-details:sync-from:cjwl')
            ->expectsOutput('没有可同步的招考公告')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    /**
     * @return array<string, mixed>
     */
    private function response(int $page, int $total, int $totalPages): array
    {
        return [
            'pagination' => [
                'total' => $total,
                'total_pages' => $totalPages,
            ],
            'data' => [
                ['page' => $page],
            ],
        ];
    }
}
