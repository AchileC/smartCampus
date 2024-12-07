<?php
// FloorEnum.php

namespace App\Utils;

/**
 * Enum FloorEnum
 *
 * Defines the floors within a building where rooms can be located.
 *
 * @package App\Utils
 */
enum FloorEnum: string
{
    /**
     * Represents the ground floor.
     */
    case GROUND = 'ground';

    /**
     * Represents the first floor.
     */
    case FIRST = 'first';

    /**
     * Represents the second floor.
     */
    case SECOND = 'second';

    /**
     * Represents the third floor.
     */
    case THIRD = 'third';
}
