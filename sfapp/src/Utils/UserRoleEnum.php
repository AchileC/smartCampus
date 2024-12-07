<?php
// UserRoleEnum.php

namespace App\Utils;

/**
 * Enum UserRoleEnum
 *
 * Defines the various roles that a user can have within the system.
 *
 * @package App\Utils
 */
enum UserRoleEnum: string
{
    /**
     * Represents a manager with administrative privileges.
     */
    case MANAGER = 'manager';

    /**
     * Represents a technician responsible for maintenance and operations.
     */
    case TECHNICIAN = 'technician';

    /**
     * Represents a regular user with limited access.
     */
    case USER = 'user';
}
