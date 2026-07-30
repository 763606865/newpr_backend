<?php

namespace App\Enums;

enum RcPaymentStatus: int
{
    case Initialized = 0;
    case Succeeded = 1;
    case Failed = 2;
    case Closed = 3;
}
