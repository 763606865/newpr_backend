<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImBusinessCardType: string implements HasLabel
{
    case RecruiterExchangePhone = 'recruiter_exchange_phone';
    case RecruiterInviteInterview = 'recruiter_invite_interview';
    case RecruiterSendOffer = 'recruiter_send_offer';
    case RecruiterReject = 'recruiter_reject';
    case JobSeekerExchangePhone = 'jobseeker_exchange_phone';
    case JobSeekerApplyResume = 'jobseeker_apply_resume';
    case JobSeekerReport = 'jobseeker_report';
    case JobSeekerNotInterested = 'jobseeker_not_interested';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RecruiterExchangePhone,
            self::JobSeekerExchangePhone => '换电话',
            self::RecruiterInviteInterview => '邀请面试',
            self::RecruiterSendOffer => '发Offer',
            self::RecruiterReject => '拒绝',
            self::JobSeekerApplyResume => '投递简历',
            self::JobSeekerReport => '举报',
            self::JobSeekerNotInterested => '不感兴趣',
        };
    }

    public function senderIdentityType(): RcIdentityType
    {
        return match ($this) {
            self::RecruiterExchangePhone,
            self::RecruiterInviteInterview,
            self::RecruiterSendOffer,
            self::RecruiterReject => RcIdentityType::Recruiter,
            self::JobSeekerExchangePhone,
            self::JobSeekerApplyResume,
            self::JobSeekerReport,
            self::JobSeekerNotInterested => RcIdentityType::JobSeeker,
        };
    }

    public function defaultTitle(): string
    {
        return (string) $this->getLabel();
    }
}
