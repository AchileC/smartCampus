<?php

namespace App\Utils;

enum UserRoleEnum: string
{
    case MANAGEER = 'manager';
    case TECHNICIAN = 'technician';
    case USER = 'user';
}