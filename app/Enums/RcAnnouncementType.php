<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcAnnouncementType: int implements HasLabel
{
    case CivilServantRecruitment = 1;
    case PublicInstitutionRecruitment = 2;
    case StateOwnedEnterpriseRecruitment = 3;
    case BankRecruitment = 4;
    case TeacherRecruitment = 5;
    case QualificationExam = 6;
    case PrivateEnterpriseRecruitment = 7;
    case ForeignEnterpriseRecruitment = 8;
    case ThreeSupportsAndOneAssistance = 9;
    case CollegeGraduateVillageOfficial = 10;
    case SelectedGraduateRecruitment = 11;
    case CommunityWorkerRecruitment = 12;
    case AuxiliaryPoliceRecruitment = 13;
    case HealthcareRecruitment = 14;
    case MilitaryCivilianRecruitment = 15;
    case FinancialInstitutionRecruitment = 16;
    case SocialOrganizationRecruitment = 17;
    case InternshipRecruitment = 18;
    case Other = 99;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CivilServantRecruitment => '公务员招录',
            self::PublicInstitutionRecruitment => '事业单位招聘',
            self::StateOwnedEnterpriseRecruitment => '国央企招聘',
            self::BankRecruitment => '银行招聘',
            self::TeacherRecruitment => '教师招聘',
            self::QualificationExam => '资格考试',
            self::PrivateEnterpriseRecruitment => '私企招聘',
            self::ForeignEnterpriseRecruitment => '外企招聘',
            self::ThreeSupportsAndOneAssistance => '三支一扶',
            self::CollegeGraduateVillageOfficial => '大学生村官',
            self::SelectedGraduateRecruitment => '选调生招录',
            self::CommunityWorkerRecruitment => '社区工作者招聘',
            self::AuxiliaryPoliceRecruitment => '辅警招聘',
            self::HealthcareRecruitment => '医疗卫生招聘',
            self::MilitaryCivilianRecruitment => '军队文职招聘',
            self::FinancialInstitutionRecruitment => '其他金融机构招聘',
            self::SocialOrganizationRecruitment => '社会组织招聘',
            self::InternshipRecruitment => '实习招聘',
            self::Other => '其他',
        };
    }
}
