<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CmsAnnouncementType: int implements HasLabel
{
    case System = 1;
    case Official = 2;
    case ExamRecruitment = 3;
    case JobRecruitment = 4;
    case LocalPolicy = 5;
    case University = 6;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::System => '系统公告',
            self::Official => '官方公告',
            self::ExamRecruitment => '招考公告',
            self::JobRecruitment => '招聘公告',
            self::LocalPolicy => '地方政策公告',
            self::University => '高校公告',
        };
    }
}
