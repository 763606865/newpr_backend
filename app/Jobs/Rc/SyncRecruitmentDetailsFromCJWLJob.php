<?php

namespace App\Jobs\Rc;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsPublishStatus;
use App\Enums\RcAnnouncementApplyDeadlineType;
use App\Enums\RcJobEmploymentType;
use App\Libs\Facades\CJWL;
use App\Models\Rc\Announcement;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use UnexpectedValueException;

class SyncRecruitmentDetailsFromCJWLJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @param  list<array<string, mixed>>  $recruitmentDetails
     */
    public function __construct(public readonly array $recruitmentDetails) {}

    public function handle(): void
    {
        foreach ($this->recruitmentDetails as $recruitmentDetail) {
            $externalId = (int) ($recruitmentDetail['id'] ?? 0);

            if ($externalId <= 0) {
                throw new UnexpectedValueException('橙就未来公告列表缺少有效的公告 ID。');
            }

            $response = CJWL::recruitmentDetail()->detail(['detail_id' => $externalId]);
            $detail = $response['data'] ?? null;

            if (! is_array($detail)) {
                throw new UnexpectedValueException("橙就未来公告 {$externalId} 详情数据结构异常。");
            }

            $this->syncAnnouncement([...$recruitmentDetail, ...$detail]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncAnnouncement(array $data): void
    {
        $externalId = (string) $data['id'];
        $announcement = Announcement::withTrashed()->firstOrNew([
            'ext_source' => 'CJWL',
            'ext_id' => $externalId,
        ]);
        $existingExtra = is_array($announcement->extra) ? $announcement->extra : [];

        $announcement->fill([
            'publisher_name' => $data['company_name'] ?? '见详情',
            'publisher_type' => $this->resolvePublisherType($data['company_type'] ?? null),
            'title' => Str::limit((string) ($data['description_title'] ?? ''), 255, ''),
            'summary' => Str::limit(strip_tags((string) ($data['description_info'] ?? '')), 1000, ''),
            'content' => $data['description_info'] ?? null,
            'link_url' => $data['source_site'] ?? null,
            'employment_types' => $this->resolveEmploymentTypes($data['hire_type'] ?? null),
            'is_nationwide' => str_contains((string) ($data['region'] ?? ''), '全国'),
            'apply_start_at' => $this->parseDate($data['recruit_start'] ?? null),
            'apply_end_at' => $this->isUntilFilled($data) ? null : $this->parseDate($data['recruit_end'] ?? null),
            'apply_deadline_type' => $this->isUntilFilled($data)
                ? RcAnnouncementApplyDeadlineType::UntilFilled
                : RcAnnouncementApplyDeadlineType::Fixed,
            'published_at' => $this->parseDate($data['create_time'] ?? null),
            'expired_at' => $this->resolveExpiredAt($data),
            'is_top' => (bool) ($data['is_top'] ?? false),
            'status' => CmsPublishStatus::Published,
            'source_name' => '橙就未来',
            'source_url' => $data['source_site'] ?? null,
            'extra' => [
                ...$existingExtra,
                'cjwl' => Arr::except($data, ['description_info']),
            ],
        ]);

        if (! $announcement->exists || $announcement->isDirty()) {
            $announcement->save();
        }
    }

    private function resolvePublisherType(mixed $companyType): CmsAnnouncementPublisherType
    {
        $companyType = trim((string) $companyType);

        foreach (CmsAnnouncementPublisherType::cases() as $type) {
            if ($type->getLabel() === $companyType) {
                return $type;
            }
        }

        return match ($companyType) {
            '国企' => CmsAnnouncementPublisherType::StateOwnedEnterprise,
            '央企' => CmsAnnouncementPublisherType::CentralEnterprise,
            default => CmsAnnouncementPublisherType::Other,
        };
    }

    /**
     * @return list<RcJobEmploymentType>
     */
    private function resolveEmploymentTypes(mixed $hireType): array
    {
        $hireType = (string) $hireType;
        $types = [];

        foreach ([
            '校招' => RcJobEmploymentType::Campus,
            '社招' => RcJobEmploymentType::FullTime,
            '实习' => RcJobEmploymentType::Internship,
            '兼职' => RcJobEmploymentType::PartTime,
            '派遣' => RcJobEmploymentType::Outsource,
            '外包' => RcJobEmploymentType::Outsource,
        ] as $keyword => $type) {
            if (str_contains($hireType, $keyword)) {
                $types[$type->value] = $type;
            }
        }

        return array_values($types);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isUntilFilled(array $data): bool
    {
        return in_array($data['public_all_type'] ?? null, ['招满为止', '招满即止'], true)
            || ($data['status'] ?? null) === '招满即止';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveExpiredAt(array $data): ?Carbon
    {
        if ($this->isUntilFilled($data)) {
            return null;
        }

        $recruitEnd = $this->parseDate($data['recruit_end'] ?? null);

        if (($data['status'] ?? null) === '已截止' && $recruitEnd === null) {
            return now();
        }

        return $recruitEnd;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        return filled($value) ? Carbon::parse((string) $value) : null;
    }
}
