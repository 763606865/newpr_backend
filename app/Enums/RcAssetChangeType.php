<?php

namespace App\Enums;

enum RcAssetChangeType: int
{
    case Grant = 1;
    case Consume = 2;
    case Refund = 3;
    case Expire = 4;
    case ManualAdjustment = 5;
}
