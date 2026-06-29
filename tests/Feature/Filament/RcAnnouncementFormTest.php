<?php

namespace Tests\Feature\Filament;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsPublishStatus;
use App\Enums\MajorLevel;
use App\Enums\MajorStatus;
use App\Enums\RcAnnouncementApplyDeadlineType;
use App\Enums\RcEducationLevel;
use App\Enums\RcJobEmploymentType;
use App\Filament\Resources\Rc\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Rc\Announcements\Pages\EditAnnouncement;
use App\Models\Area;
use App\Models\Major;
use App\Models\Rc\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class RcAnnouncementFormTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    /**
     * @return array<int, string>
     */
    private function announcementPermissions(): array
    {
        return [
            'ViewAny:Announcement',
            'View:Announcement',
            'Create:Announcement',
            'Update:Announcement',
            'Delete:Announcement',
            'DeleteAny:Announcement',
            'Restore:Announcement',
            'ForceDelete:Announcement',
            'ForceDeleteAny:Announcement',
            'RestoreAny:Announcement',
        ];
    }

    public function test_create_rc_announcement_form_saves_core_fields_and_relations(): void
    {
        $this->actingAsFilamentAdmin($this->announcementPermissions());
        $this->seedAreas();
        $this->seedMajor();

        Livewire::test(CreateAnnouncement::class)
            ->assertSuccessful()
            ->assertSee('官网外链')
            ->assertSee('面向届别')
            ->fillForm([
                'title' => '中粮集团2026届校园招聘',
                'publisher_name' => '中粮集团有限公司',
                'publisher_type' => CmsAnnouncementPublisherType::CentralEnterprise,
                'link_url' => 'https://cofco.example.com/campus',
                'employment_types' => [
                    RcJobEmploymentType::Internship->value,
                    RcJobEmploymentType::Campus->value,
                ],
                'graduation_years' => [2026, 2027],
                'education_level' => RcEducationLevel::Bachelor,
                'major_requirement' => '计算机、财务等相关专业',
                'is_nationwide' => false,
                'city_codes' => ['360100'],
                'major_codes' => ['080901'],
                'apply_start_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'apply_end_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'apply_deadline_type' => RcAnnouncementApplyDeadlineType::Fixed,
                'status' => CmsPublishStatus::Draft,
                'is_top' => false,
                'sort' => 0,
                'read_count' => 0,
            ])
            ->call('create')
            ->assertNotified();

        $announcement = Announcement::query()->where('title', '中粮集团2026届校园招聘')->first();

        $this->assertNotNull($announcement);
        $this->assertSame('中粮集团有限公司', $announcement->publisher_name);
        $this->assertSame('https://cofco.example.com/campus', $announcement->link_url);
        $this->assertSame([3, 4], $announcement->employment_types);
        $this->assertSame([2026, 2027], $announcement->graduation_years);
        $this->assertSame(['360100'], $announcement->cities()->pluck('city_code')->all());
        $this->assertSame(['080901'], $announcement->majors()->pluck('major_code')->all());
    }

    public function test_create_form_clears_apply_end_at_when_deadline_type_is_until_filled(): void
    {
        $this->actingAsFilamentAdmin($this->announcementPermissions());

        Livewire::test(CreateAnnouncement::class)
            ->fillForm([
                'title' => '招满即止公告',
                'publisher_name' => '测试企业',
                'publisher_type' => CmsAnnouncementPublisherType::ListedCompany,
                'link_url' => 'https://example.com/until-filled',
                'apply_deadline_type' => RcAnnouncementApplyDeadlineType::UntilFilled,
                'apply_end_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'status' => CmsPublishStatus::Draft,
                'is_top' => false,
                'sort' => 0,
                'read_count' => 0,
            ])
            ->call('create')
            ->assertNotified();

        $announcement = Announcement::query()->where('title', '招满即止公告')->first();

        $this->assertNotNull($announcement);
        $this->assertSame(RcAnnouncementApplyDeadlineType::UntilFilled, $announcement->apply_deadline_type);
        $this->assertNull($announcement->apply_end_at);
    }

    public function test_edit_rc_announcement_form_loads_city_and_major_codes(): void
    {
        $this->actingAsFilamentAdmin($this->announcementPermissions());
        $this->seedAreas();
        $this->seedMajor();

        $announcement = Announcement::query()->create([
            'title' => '苏州地铁2026届招聘',
            'publisher_name' => '苏州轨道交通集团',
            'publisher_type' => CmsAnnouncementPublisherType::StateOwnedEnterprise,
            'link_url' => 'https://example.com/suzhou-metro',
            'employment_types' => [RcJobEmploymentType::Campus->value],
            'graduation_years' => [2026],
            'education_level' => RcEducationLevel::Master,
            'status' => CmsPublishStatus::Published,
            'apply_deadline_type' => RcAnnouncementApplyDeadlineType::Fixed,
            'published_at' => now(),
        ]);

        $announcement->syncCityCodes(['320500']);
        $announcement->syncMajorCodes(['080901']);

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet([
                'title' => '苏州地铁2026届招聘',
                'publisher_name' => '苏州轨道交通集团',
                'city_codes' => ['320500'],
                'major_codes' => ['080901'],
            ]);
    }

    private function seedAreas(): void
    {
        Area::query()->create([
            'name' => '江西省',
            'code' => '360000',
            'parent_code' => '000000',
            'level' => 1,
            'type' => null,
        ]);

        Area::query()->create([
            'name' => '南昌市',
            'code' => '360100',
            'parent_code' => '360000',
            'level' => 2,
            'type' => null,
        ]);

        Area::query()->create([
            'name' => '苏州市',
            'code' => '320500',
            'parent_code' => '320000',
            'level' => 2,
            'type' => null,
        ]);
    }

    private function seedMajor(): void
    {
        Major::query()->create([
            'full_code' => '080901',
            'name' => '计算机科学与技术',
            'level' => MajorLevel::Major,
            'parent_code' => '0809',
            'type' => '高职专科',
            'sort' => 0,
            'status' => MajorStatus::Enabled,
        ]);
    }
}
