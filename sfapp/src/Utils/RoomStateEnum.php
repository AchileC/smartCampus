<?php

namespace App\Utils;

enum RoomStateEnum: string
{
    case OK = 'ok';
    case PROBLEM = 'problem';
    case CRITICAL = 'critical';

}
