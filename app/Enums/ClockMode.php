<?php

namespace App\Enums;

enum ClockMode: int
{
    case Normal = 1;
    case ForceOverwrite = 2;
}
