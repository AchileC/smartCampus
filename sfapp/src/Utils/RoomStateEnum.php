<?php

namespace App\Utils;

enum RoomStateEnum: string
{
    case NONE = 'none';
    case WAITING = 'waiting';
    case OK = 'ok';
    case PROBLEM = 'problem';
    case CRITICAL = 'critical';
}

