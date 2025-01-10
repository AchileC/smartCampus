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
     * Indicates that the sensors are probably broken or disconnected and require maintenance.
     */
    case NOT_WORKING = 'not working';
}
