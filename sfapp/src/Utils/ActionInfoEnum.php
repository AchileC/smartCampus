<?php
// ActionInfoEnum.php

namespace App\Utils;

/**
 * Enum ActionInfoEnum
 *
 * Defines the types of actions that can be performed within the system.
 *
 * @package App\Utils
 */
enum ActionInfoEnum: string
{
    /**
     * Represents the assignment of an acquisition system to a room.
     */
    case ASSIGNMENT = 'assignment';

    /**
     * Represents the unassignment of an acquisition system from a room.
     */
    case UNASSIGNMENT = 'unassignment';
}
