<?php

namespace App\Utils;

enum RoomStateEnum: string
{
    case OK = 'ok';
    case PROBLEM = 'problem';
    case CRITICAL = 'critical';
    case PENDING_ASSIGNMENT = 'pending assignment';
    case PENDING_UNASSIGNMENT = 'pending unassignment';
    case NOT_LINKED = 'not linked';
}

