<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CmsAnnouncementPublisherType: int implements HasLabel
{
    case System = 0;
    case StateOwnedEnterprise = 1;
    case CentralEnterprise = 2;
    case PublicInstitution = 3;
    case Government = 4;
    case Bank = 5;
    case School = 6;
    case PrivateEnterprise = 7;
    case ForeignEnterprise = 8;
    case JointVenture = 9;
    case Hospital = 10;
    case ResearchInstitute = 11;
    case IndustryAssociation = 12;
    case SocialOrganization = 13;
    case ListedCompany = 14;
    case NonProfitOrganization = 15;
    case Military = 16;
    case Other = 99;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::System => '系统',
            self::StateOwnedEnterprise => '国有企业',
            self::CentralEnterprise => '中央企业',
            self::PublicInstitution => '事业单位',
            self::Government => '政府机关',
            self::Bank => '银行',
            self::School => '学校',
            self::PrivateEnterprise => '民营企业',
            self::ForeignEnterprise => '外资企业',
            self::JointVenture => '合资企业',
            self::Hospital => '医院',
            self::ResearchInstitute => '科研院所',
            self::IndustryAssociation => '行业协会',
            self::SocialOrganization => '社会组织',
            self::ListedCompany => '上市公司',
            self::NonProfitOrganization => '非营利组织',
            self::Military => '军队',
            self::Other => '其他',
        };
    }
}
