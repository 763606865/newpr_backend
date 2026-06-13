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
        };
    }
}
