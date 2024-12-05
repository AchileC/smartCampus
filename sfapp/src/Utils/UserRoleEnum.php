<?php

namespace App\Utils;

enum UserRoleEnum: string
{
    case MANAGER = 'manager';
    case TECHNICIAN = 'technician';
    case USER = 'user';
}