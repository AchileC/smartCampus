<?php

namespace App\Utils;

enum ActionInfoEnum : string
{
    /**
     * Represents the assignment of an acquisition system to a room.
     */
    case ASSIGNMENT = 'assignment';

    /**
     * Represents the unassignment of an acquisition system from a room.
     */
    case UNASSIGNMENT = 'unassignment';

    /**
     * Represents the maintenance of an acquisition system probably broken or disconnected from a room.
     */
    case MAINTENANCE = 'maintenance';
}
