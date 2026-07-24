<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcNotificationType: int implements HasLabel
{
    case InterviewInvitation = 1;
    case OfferSent = 2;
    case ApplicationStatusChanged = 3;
    case InterviewInvitationAccepted = 4;
    case InterviewInvitationRejected = 5;
    case OfferAcceptedByCandidate = 6;
    case OfferRejectedByCandidate = 7;
    case SchoolActivityCompanyInvited = 8;
    case SchoolActivityCompanyApproved = 9;
    case CompanyAuditResult = 10;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::InterviewInvitation => '面试邀请',
            self::OfferSent => 'Offer通知',
            self::ApplicationStatusChanged => '投递状态变更',
            self::InterviewInvitationAccepted => '面试邀请已接受',
            self::InterviewInvitationRejected => '面试邀请已拒绝',
            self::OfferAcceptedByCandidate => 'Offer已接受',
            self::OfferRejectedByCandidate => 'Offer已拒绝',
            self::SchoolActivityCompanyInvited => '校招活动邀约',
            self::SchoolActivityCompanyApproved => '校招活动审批通过',
            self::CompanyAuditResult => '企业审核通知',
        };
    }
}
