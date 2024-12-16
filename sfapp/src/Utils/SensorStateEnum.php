<?php
// SensorStateEnum.php

namespace App\Utils;

/**
 * Enum SensorStateEnum
 *
 * Defines the possible states of sensors associated with a room.
 *
 * @package App\Utils
 */
enum SensorStateEnum: string
{
    /**
     * Indicates that the sensors are not linked to any acquisition system.
     */
    case NOT_LINKED = 'not linked';

    /**
     * Indicates that the sensors are linked and operational.
     */
    case LINKED = 'linked';

    /**
     * Indicates that an assignment of sensors to an acquisition system is in progress.
     */
    case ASSIGNMENT = 'assignment';

    /**
     * Indicates that an unassignment of sensors from an acquisition system is in progress.
     */
    case UNASSIGNMENT = 'unassignment';

    /**
     * Indicates that the sensors are probably broken or disconnected and require maintenance.
     */
    case NOT_WORKING = 'not working';
}
