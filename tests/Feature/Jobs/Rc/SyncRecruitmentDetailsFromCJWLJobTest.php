<?php

namespace Tests\Feature\Jobs\Rc;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsPublishStatus;
use App\Enums\RcAnnouncementApplyDeadlineType;
use App\Enums\RcAnnouncementType;
use App\Enums\RcJobEmploymentType;
use App\Jobs\Rc\SyncRecruitmentDetailsFromCJWLJob;
use App\Libs\Facades\CJWL;
use App\Libs\ThirdParty\CJWL\Api\RecruitmentDetail;
use App\Models\Area;
use App\Models\Rc\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use UnexpectedValueException;

class SyncRecruitmentDetailsFromCJWLJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fetches_details_and_creates_a_mapped_announcement(): void
    {
        Area::query()->create([
            'name' => '朝阳区',
            'code' => '110105',
            'parent_code' => '110100',
            'level' => 3,
        ]);

        $api = Mockery::mock(RecruitmentDetail::class);
        $api->shouldReceive('detail')->once()->with(['detail_id' => 54001])->andReturn([
            'code' => 200,
            'data' => $this->detailData(),
        ]);
        CJWL::shouldReceive('recruitmentDetail')->once()->andReturn($api);

        (new SyncRecruitmentDetailsFromCJWLJob([
            [
                'id' => 54001,
                'description_info' => '列表摘要',
                'is_hot' => 1,
            ],
        ]))->handle();

        $announcement = Announcement::query()->sole();

        $this->assertSame('CJWL', $announcement->ext_source);
        $this->assertSame('54001', $announcement->ext_id);
        $this->assertSame('XX单位2026年招聘公告', $announcement->title);
        $this->assertSame('公告完整正文', $announcement->summary);
        $this->assertSame('公告完整正文', $announcement->content);
        $this->assertSame(CmsAnnouncementPublisherType::PublicInstitution, $announcement->publisher_type);
        $this->assertSame(RcAnnouncementType::PublicInstitutionRecruitment, $announcement->announcement_type);
        $this->assertSame(50, $announcement->recruitment_count);
        $this->assertSame([RcJobEmploymentType::Campus->value, RcJobEmploymentType::FullTime->value], $announcement->employment_types);
        $this->assertSame(CmsPublishStatus::Published, $announcement->status);
        $this->assertSame(RcAnnouncementApplyDeadlineType::Fixed, $announcement->apply_deadline_type);
        $this->assertSame('2026-08-01 00:00:00', $announcement->apply_start_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-31 23:59:59', $announcement->apply_end_at?->format('Y-m-d H:i:s'));
        $this->assertSame(1001, $announcement->extra['cjwl']['company_id']);
        $this->assertSame(1, $announcement->extra['cjwl']['is_hot']);
        $this->assertSame('教育', $announcement->extra['display']['industry']);
        $this->assertSame(10, $announcement->extra['display']['positions_number']);
        $this->assertTrue($announcement->extra['display']['has_establishment']);
        $this->assertSame(['110105'], $announcement->cities()->pluck('city_code')->all());
        $this->assertArrayNotHasKey('description_info', $announcement->extra['cjwl']);
    }

    public function test_it_updates_the_same_external_announcement_without_creating_a_duplicate(): void
    {
        Announcement::query()->create([
            'ext_source' => 'CJWL',
            'ext_id' => '54001',
            'title' => '旧标题',
            'extra' => ['manual_note' => '保留内容'],
        ]);

        $api = Mockery::mock(RecruitmentDetail::class);
        $api->shouldReceive('detail')->twice()->with(['detail_id' => 54001])->andReturn([
            'code' => 200,
            'data' => $this->detailData(),
        ]);
        CJWL::shouldReceive('recruitmentDetail')->twice()->andReturn($api);

        $job = new SyncRecruitmentDetailsFromCJWLJob([['id' => 54001]]);
        $job->handle();
        $job->handle();

        $this->assertSame(1, Announcement::query()->count());
        $announcement = Announcement::query()->sole();
        $this->assertSame('XX单位2026年招聘公告', $announcement->title);
        $this->assertSame('保留内容', $announcement->extra['manual_note']);
    }

    public function test_it_maps_until_filled_announcements_without_an_end_date(): void
    {
        $data = $this->detailData();
        $data['public_all_type'] = '招满为止';

        $api = Mockery::mock(RecruitmentDetail::class);
        $api->shouldReceive('detail')->once()->andReturn(['code' => 200, 'data' => $data]);
        CJWL::shouldReceive('recruitmentDetail')->once()->andReturn($api);

        (new SyncRecruitmentDetailsFromCJWLJob([['id' => 54001]]))->handle();

        $announcement = Announcement::query()->sole();
        $this->assertSame(RcAnnouncementApplyDeadlineType::UntilFilled, $announcement->apply_deadline_type);
        $this->assertNull($announcement->apply_end_at);
        $this->assertNull($announcement->expired_at);
    }

    public function test_it_preserves_a_display_count_when_the_numeric_count_is_not_available(): void
    {
        $data = $this->detailData();
        $data['hire_count'] = 0;
        $data['hire_display_type'] = '若干';
        $data['description_title'] = '某外企实习招聘公告';
        $data['company_type'] = '外资企业';

        $api = Mockery::mock(RecruitmentDetail::class);
        $api->shouldReceive('detail')->once()->andReturn(['code' => 200, 'data' => $data]);
        CJWL::shouldReceive('recruitmentDetail')->once()->andReturn($api);

        (new SyncRecruitmentDetailsFromCJWLJob([['id' => 54001]]))->handle();

        $announcement = Announcement::query()->sole();
        $this->assertNull($announcement->recruitment_count);
        $this->assertSame('若干', $announcement->extra['display']['hire_count_text']);
        $this->assertSame(RcAnnouncementType::ForeignEnterpriseRecruitment, $announcement->announcement_type);
    }

    public function test_it_rejects_a_detail_response_without_data(): void
    {
        $api = Mockery::mock(RecruitmentDetail::class);
        $api->shouldReceive('detail')->once()->andReturn(['code' => 200]);
        CJWL::shouldReceive('recruitmentDetail')->once()->andReturn($api);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('橙就未来公告 54001 详情数据结构异常。');

        (new SyncRecruitmentDetailsFromCJWLJob([['id' => 54001]]))->handle();
    }

    /**
     * @return array<string, mixed>
     */
    private function detailData(): array
    {
        return [
            'id' => 54001,
            'description_title' => 'XX单位2026年招聘公告',
            'description_info' => '公告完整正文',
            'hire_count' => 50,
            'region' => '北京市朝阳区',
            'status' => '进行中',
            'source_site' => 'https://example.com/announcement/54001',
            'hire_type' => '校招,社招',
            'company_type' => '事业单位',
            'industry' => '教育',
            'publish_year' => '2026',
            'create_time' => '2026-08-01T10:00:00',
            'update_time' => '2026-08-05T12:00:00',
            'company_id' => 1001,
            'ori_id' => 2002,
            'recruit_start' => '2026-08-01T00:00:00',
            'recruit_end' => '2026-08-31T23:59:59',
            'is_top' => 0,
            'is_hot' => 1,
            'special_recruitment' => 0,
            'positions_number' => 10,
            'has_establishment' => 1,
            'public_all_type' => '指定日期',
        ];
    }
}
