<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImInteractionRequestType: string implements HasLabel
{
    case ExchangeContact = 'exchange_contact';
    case RespondInterviewInvitation = 'respond_interview_invitation';
    case RespondOffer = 'respond_offer';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ExchangeContact => '交换联系方式',
            self::RespondInterviewInvitation => '处理面试邀请',
            self::RespondOffer => '处理 Offer',
        };
    }

    public function defaultTitle(): string
    {
        return (string) $this->getLabel();
    }
}
