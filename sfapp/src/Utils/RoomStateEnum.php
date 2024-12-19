<?php
// RoomStateEnum.php

namespace App\Utils;

/**
 * Enum RoomStateEnum
 *
 * Defines the various states a room can be in based on sensor data and system assignments.
 *
 * @package App\Utils
 */
enum RoomStateEnum: string
{
    /**
     * Indicates that there is no specific state assigned to the room.
     */
    case NONE = 'none';

    /**
     * Indicates that the room is awaiting an assignment or unassignment of an acquisition system.
     */
    case WAITING = 'waiting';

    /**
     * Indicates that the room is in a stable condition with normal sensor readings.
     */
    case STABLE = 'stable';

    /**
     * Indicates that the room is at risk due to abnormal sensor readings.
     */
    case AT_RISK = 'at risk';

    /**
     * Indicates that the room is in a critical condition requiring immediate attention.
     */
    case CRITICAL = 'critical';
}
