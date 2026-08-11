<?php

namespace App\Console\Commands\Rc\CJWL;

use App\Jobs\Rc\SyncRecruitmentDetailsFromCJWLJob;
use App\Libs\Exceptions\BadRequestException;
use App\Libs\Facades\CJWL;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use UnexpectedValueException;

#[Signature('rc:recruitment-details:sync-from:cjwl')]
#[Description('从橙就未来同步招考公告过来')]
class SyncRecruitmentDetailsFromCJWL extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('开始从橙就未来同步招考公告过来');
        $currentPage = 1;

        $params = [
            'recruit_start' => Carbon::tomorrow()->toDateTimeString(),
        ];

        [$pagination, $data] = $this->resolveOpenApi($currentPage, $params);

        $total = (int) ($pagination['total'] ?? 0);
        $perPage = (int) ($pagination['per_page'] ?? count($data));
        $totalPages = max(1, (int) ($pagination['total_pages'] ?? 1));

        if ($total === 0) {
            $this->info('没有可同步的招考公告');

            return self::SUCCESS;
        }

        $this->info(sprintf('共找到 %d 条招考公告，每页 %d 条分发到队列', $total, $perPage));

        $this->dispatchPullDownEvent($data);

        for ($currentPage = 2; $currentPage <= $totalPages; $currentPage++) {
            [, $data] = $this->resolveOpenApi($currentPage, $params);
            $this->dispatchPullDownEvent($data);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{0: array<string, mixed>, 1: array<int, mixed>}
     *
     * @throws BadRequestException
     * @throws BindingResolutionException
     * @throws \JsonException
     */
    private function resolveOpenApi(int $page, array $params): array
    {
        $response = CJWL::recruitmentDetail()->query([
            ...$params,
            'page' => $page,
        ]);

        if (! isset($response['pagination'], $response['data'])) {
            throw new UnexpectedValueException('橙就未来后端数据结构异常。');
        }

        return [$response['pagination'], $response['data']];
    }

    private function dispatchPullDownEvent(array $data): void
    {
        SyncRecruitmentDetailsFromCJWLJob::dispatch($data);
    }
}
