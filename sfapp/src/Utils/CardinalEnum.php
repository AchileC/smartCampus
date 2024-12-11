<?php
// CardinalEnum.php

namespace App\Utils;

/**
 * Enum CardinalEnum
 *
 * Defines the cardinal directions associated with a room.
 *
 * @package App\Utils
 */
enum CardinalEnum: string
{
    /**
     * Represents the north direction.
     */
    case NORTH = 'north';

    /**
     * Represents the south direction.
     */
    case SOUTH = 'south';

    /**
     * Represents the east direction.
     */
    case EAST = 'east';

    /**
     * Represents the west direction.
     */
    case WEST = 'west';
}
