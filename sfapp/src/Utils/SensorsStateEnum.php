<?php

namespace App\Utils;

enum SensorsStateEnum: string
{
    case PENDING_ASSIGNMENT = 'pending assignment';
    case PENDING_UNASSIGNMENT = 'pending unassignment';
    case NOT_LINKED = 'not linked';
    case PROBABLY_BROKEN = 'probably broken';

}
