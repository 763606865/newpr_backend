<?php

namespace App\Jobs\Rc;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsPublishStatus;
use App\Enums\RcAnnouncementApplyDeadlineType;
use App\Enums\RcAnnouncementType;
use App\Enums\RcJobEmploymentType;
use App\Libs\Facades\CJWL;
use App\Models\Area;
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
        $existingDisplay = is_array($existingExtra['display'] ?? null) ? $existingExtra['display'] : [];

        $announcement->fill([
            'publisher_name' => $data['company_name'] ?? '见详情',
            'publisher_type' => $this->resolvePublisherType($data['company_type'] ?? null),
            'announcement_type' => $this->resolveAnnouncementType($data),
            'title' => Str::limit((string) ($data['description_title'] ?? ''), 255, ''),
            'summary' => Str::limit(strip_tags((string) ($data['description_info'] ?? '')), 1000, ''),
            'content' => $data['description_info'] ?? null,
            'link_url' => $data['source_site'] ?? null,
            'recruitment_count' => $this->resolveRecruitmentCount($data['hire_count'] ?? null),
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
                'display' => [
                    ...$existingDisplay,
                    'hire_count_text' => $this->resolveHireCountText($data),
                    'region' => $this->nullableString($data['region'] ?? null),
                    'industry' => $this->nullableString($data['industry'] ?? null),
                    'positions_number' => $this->nullablePositiveInteger($data['positions_number'] ?? null),
                    'special_recruitment' => $this->nullableBoolean($data['special_recruitment'] ?? null),
                    'is_hot' => $this->nullableBoolean($data['is_hot'] ?? null),
                    'has_establishment' => $this->nullableBoolean($data['has_establishment'] ?? null),
                    'publish_year' => $this->nullableString($data['publish_year'] ?? null),
                    'public_all_type' => $this->nullableString($data['public_all_type'] ?? null),
                ],
                'cjwl' => Arr::except($data, ['description_info']),
            ],
        ]);

        if (! $announcement->exists || $announcement->isDirty()) {
            $announcement->save();
        }

        $announcement->syncCityCodes($this->resolveCityCodes($data['region'] ?? null));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveAnnouncementType(array $data): RcAnnouncementType
    {
        $haystack = implode(' ', array_filter([
            $this->nullableString($data['description_title'] ?? null),
            $this->nullableString($data['company_type'] ?? null),
            $this->nullableString($data['hire_type'] ?? null),
            $this->nullableString($data['industry'] ?? null),
        ]));

        return match (true) {
            str_contains($haystack, '三支一扶') => RcAnnouncementType::ThreeSupportsAndOneAssistance,
            str_contains($haystack, '大学生村官'),
            str_contains($haystack, '村官') => RcAnnouncementType::CollegeGraduateVillageOfficial,
            str_contains($haystack, '选调生') => RcAnnouncementType::SelectedGraduateRecruitment,
            str_contains($haystack, '公务员') => RcAnnouncementType::CivilServantRecruitment,
            str_contains($haystack, '军队文职') => RcAnnouncementType::MilitaryCivilianRecruitment,
            str_contains($haystack, '辅警') => RcAnnouncementType::AuxiliaryPoliceRecruitment,
            str_contains($haystack, '社区工作者') => RcAnnouncementType::CommunityWorkerRecruitment,
            str_contains($haystack, '医疗卫生'),
            str_contains($haystack, '医院') => RcAnnouncementType::HealthcareRecruitment,
            str_contains($haystack, '教师招聘') => RcAnnouncementType::TeacherRecruitment,
            str_contains($haystack, '教师资格'),
            str_contains($haystack, '资格考试') => RcAnnouncementType::QualificationExam,
            str_contains($haystack, '事业单位') => RcAnnouncementType::PublicInstitutionRecruitment,
            str_contains($haystack, '央企'),
            str_contains($haystack, '国企'),
            str_contains($haystack, '国有企业'),
            str_contains($haystack, '中央企业') => RcAnnouncementType::StateOwnedEnterpriseRecruitment,
            str_contains($haystack, '银行') => RcAnnouncementType::BankRecruitment,
            str_contains($haystack, '金融') => RcAnnouncementType::FinancialInstitutionRecruitment,
            str_contains($haystack, '外企'),
            str_contains($haystack, '外资企业') => RcAnnouncementType::ForeignEnterpriseRecruitment,
            str_contains($haystack, '民营企业'),
            str_contains($haystack, '私企') => RcAnnouncementType::PrivateEnterpriseRecruitment,
            str_contains($haystack, '社会组织') => RcAnnouncementType::SocialOrganizationRecruitment,
            str_contains($haystack, '实习') => RcAnnouncementType::InternshipRecruitment,
            default => RcAnnouncementType::Other,
        };
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

    private function resolveRecruitmentCount(mixed $value): ?int
    {
        return $this->nullablePositiveInteger($value);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveHireCountText(array $data): ?string
    {
        $count = $this->resolveRecruitmentCount($data['hire_count'] ?? null);

        if ($count !== null) {
            return (string) $count;
        }

        return $this->nullableString($data['hire_display_type'] ?? null);
    }

    /**
     * @return list<string>
     */
    private function resolveCityCodes(mixed $region): array
    {
        $region = $this->nullableString($region);

        if ($region === null || str_contains($region, '全国')) {
            return [];
        }

        $area = Area::query()
            ->whereRaw('? LIKE CONCAT(\'%\', name, \'%\')', [$region])
            ->orderByDesc('level')
            ->orderByRaw('CHAR_LENGTH(name) DESC')
            ->first();

        return $area === null ? [] : [$area->code];
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
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullablePositiveInteger(mixed $value): ?int
    {
        if (! is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }

    private function nullableBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (bool) $value;
    }
}
