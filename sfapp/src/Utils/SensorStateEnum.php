<?php

namespace App\Utils;

enum SensorStateEnum: string
{
    case NOT_LINKED = 'not linked';
    case LINKED = 'linked';
    case ASSIGNMENT = 'assignment';
    case UNASSIGNMENT = 'unassignment';
    case PROBABLY_BROKEN = 'probably broken';

}
