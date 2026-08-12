<?php

namespace Tests\Unit\Enums;

use App\Enums\RcAnnouncementType;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RcAnnouncementTypeTest extends TestCase
{
    #[DataProvider('announcementTypes')]
    public function test_it_has_stable_values_and_labels(
        RcAnnouncementType $type,
        int $value,
        string $label,
    ): void {
        $this->assertSame($value, $type->value);
        $this->assertSame($label, $type->getLabel());
    }

    /**
     * @return iterable<string, array{RcAnnouncementType, int, string}>
     */
    public static function announcementTypes(): iterable
    {
        yield '私企招聘' => [RcAnnouncementType::PrivateEnterpriseRecruitment, 7, '私企招聘'];
        yield '外企招聘' => [RcAnnouncementType::ForeignEnterpriseRecruitment, 8, '外企招聘'];
        yield '三支一扶' => [RcAnnouncementType::ThreeSupportsAndOneAssistance, 9, '三支一扶'];
        yield '大学生村官' => [RcAnnouncementType::CollegeGraduateVillageOfficial, 10, '大学生村官'];
        yield '选调生' => [RcAnnouncementType::SelectedGraduateRecruitment, 11, '选调生招录'];
        yield '社区工作者' => [RcAnnouncementType::CommunityWorkerRecruitment, 12, '社区工作者招聘'];
        yield '辅警' => [RcAnnouncementType::AuxiliaryPoliceRecruitment, 13, '辅警招聘'];
        yield '医疗卫生' => [RcAnnouncementType::HealthcareRecruitment, 14, '医疗卫生招聘'];
        yield '军队文职' => [RcAnnouncementType::MilitaryCivilianRecruitment, 15, '军队文职招聘'];
        yield '其他金融机构' => [RcAnnouncementType::FinancialInstitutionRecruitment, 16, '其他金融机构招聘'];
        yield '社会组织' => [RcAnnouncementType::SocialOrganizationRecruitment, 17, '社会组织招聘'];
        yield '实习' => [RcAnnouncementType::InternshipRecruitment, 18, '实习招聘'];
    }
}
