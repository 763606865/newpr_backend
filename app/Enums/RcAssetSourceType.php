<?php

namespace App\Enums;

enum RcAssetSourceType: int
{
    case Unknown = 0;
    case Order = 1;
    case System = 2;
    case Manual = 3;
}
