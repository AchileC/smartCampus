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
     * Indicates that there is no data from the api for the moment or that the data are aberrants
     */
    case NO_DATA = 'no data';


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
