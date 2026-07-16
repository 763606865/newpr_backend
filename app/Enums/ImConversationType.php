<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImConversationType: string implements HasLabel
{
    case Single = 'single';
    case Group = 'group';
    case Chatroom = 'chatroom';
    case LiveRoom = 'live_room';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Single => '单聊',
            self::Group => '群聊',
            self::Chatroom => '聊天室',
            self::LiveRoom => '直播间',
        };
    }
}
