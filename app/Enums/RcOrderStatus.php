<?php

namespace App\Enums;

enum RcOrderStatus: int
{
    case PendingPayment = 0;
    case Completed = 1;
    case Canceled = 2;
    case Closed = 3;
}
