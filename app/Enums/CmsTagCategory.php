<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CmsTagCategory: string implements HasLabel
{
    case Rc = 'rc';
    case Exam = 'exam';
    case ExamRecruitment = 'exam_recruitment';
    case SchoolExam = 'school_exam';
    case CertificateExam = 'certificate_exam';
    case Announcement = 'announcement';
    case Article = 'article';
    case Job = 'job';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Rc => '招聘类型 (rc)',
            self::Exam => '考试 (exam)',
            self::ExamRecruitment => '招考 (exam_recruitment)',
            self::SchoolExam => '学校考试 (school_exam)',
            self::CertificateExam => '证书考试 (certificate_exam)',
            self::Announcement => '公告 (announcement)',
            self::Article => '文章 (article)',
            self::Job => '职位 (job)',
        };
    }
}
