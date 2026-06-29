<?php

namespace Tests\Unit\Models\Rc;

use App\Enums\CmsPublishStatus;
use App\Enums\RcAnnouncementApplyDeadlineType;
use App\Enums\RcJobEmploymentType;
use App\Models\Area;
use App\Models\Major;
use App\Models\Rc\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_city_and_major_codes(): void
    {
        Area::query()->create([
            'name' => '苏州市',
            'code' => '320500',
            'parent_code' => '320000',
            'level' => 2,
            'type' => null,
        ]);

        Major::query()->create([
            'full_code' => '080901',
            'name' => '计算机科学与技术',
            'level' => 3,
            'parent_code' => '0809',
            'type' => '高职专科',
            'sort' => 0,
            'status' => 1,
        ]);

        $announcement = Announcement::query()->create([
            'title' => '测试招聘公告',
            'publisher_name' => '测试企业',
            'link_url' => 'https://example.com/jobs',
            'status' => CmsPublishStatus::Draft,
        ]);

        $announcement->syncCityCodes(['320500']);
        $announcement->syncMajorCodes(['080901']);

        $this->assertSame(['320500'], $announcement->cities()->pluck('city_code')->all());
        $this->assertSame(['080901'], $announcement->majors()->pluck('major_code')->all());
    }

    public function test_apply_status_labels(): void
    {
        $open = Announcement::query()->create([
            'title' => '正在报名公告',
            'publisher_name' => '测试企业',
            'link_url' => 'https://example.com/open',
            'status' => CmsPublishStatus::Published,
            'apply_start_at' => now()->subDay(),
            'apply_end_at' => now()->addWeek(),
            'apply_deadline_type' => RcAnnouncementApplyDeadlineType::Fixed,
        ]);

        $closingSoon = Announcement::query()->create([
            'title' => '即将截止公告',
            'publisher_name' => '测试企业',
            'link_url' => 'https://example.com/closing',
            'status' => CmsPublishStatus::Published,
            'apply_start_at' => now()->subDay(),
            'apply_end_at' => now()->addDay(),
            'apply_deadline_type' => RcAnnouncementApplyDeadlineType::Fixed,
        ]);

        $untilFilled = Announcement::query()->create([
            'title' => '招满即止公告',
            'publisher_name' => '测试企业',
            'link_url' => 'https://example.com/until-filled',
            'status' => CmsPublishStatus::Published,
            'apply_deadline_type' => RcAnnouncementApplyDeadlineType::UntilFilled,
        ]);

        $this->assertSame('正在报名', $open->applyStatusLabel());
        $this->assertSame('即将截止', $closingSoon->applyStatusLabel());
        $this->assertSame('正在报名', $untilFilled->applyStatusLabel());
        $this->assertSame(
            ['实习生招聘', '应届校园招聘'],
            Announcement::query()->create([
                'title' => '多类型公告',
                'publisher_name' => '测试企业',
                'link_url' => 'https://example.com/types',
                'employment_types' => [
                    RcJobEmploymentType::Internship->value,
                    RcJobEmploymentType::Campus->value,
                ],
                'status' => CmsPublishStatus::Draft,
            ])->employmentTypeLabels(),
        );
    }
}
