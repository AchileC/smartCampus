<?php

namespace App\Utils;

enum RoomStateEnum: string
{
    case NONE = 'none';
    case WAITING = 'waiting';
    case STABLE = 'stable';
    case AT_RISK = 'at risk';
    case CRITICAL = 'critical';
}

