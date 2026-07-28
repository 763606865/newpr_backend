<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcAssetCode: string implements HasLabel
{
    case FullTimeJobPosting = 'job_posting_full_time';
    case CampusJobPosting = 'job_posting_campus';
    case ResumeRefresh = 'resume_refresh';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FullTimeJobPosting => '社招全职职位发布',
            self::CampusJobPosting => '校招职位发布',
            self::ResumeRefresh => '简历刷新',
        };
    }
}
