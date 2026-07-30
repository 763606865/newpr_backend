<?php

namespace App\Enums;

enum RcOrderPayStatus: int
{
    case Pending = 0;
    case Paid = 1;
    case Failed = 2;
    case Refunded = 3;
}
