<?php

namespace App\Utils;

enum ActionStateEnum : string
{
    case TO_DO = 'to do';
    case DOING = 'doing';
    case DONE  = 'done';
}
