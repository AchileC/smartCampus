<?php

namespace App\Utils;

enum ActionInfoEnum : string
{
    case ASSIGNMENT = 'assignment';
    case UNASSIGNMENT = 'unassignment';
    case SWITCH = 'switch';
    case REPLACEMENT = 'replacement';
}
