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
    case ROLE_MANAGER = 'ROLE_MANAGER';

    /**
     * Represents a technician responsible for maintenance and operations.
     */
    case ROLE_TECHNICIAN = 'ROLE_TECHNICIAN';

    /**
     * Represents a regular user with limited access.
     */
    case ROLE_USER = 'ROLE_USER';
}
