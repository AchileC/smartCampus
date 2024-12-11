<?php
// ActionStateEnum.php

namespace App\Utils;

/**
 * Enum ActionStateEnum
 *
 * Defines the possible states of an action within the system.
 *
 * @package App\Utils
 */
enum ActionStateEnum: string
{
    /**
     * Indicates that the action is yet to be started.
     */
    case TO_DO = 'to do';

    /**
     * Indicates that the action is currently in progress.
     */
    case DOING = 'doing';

    /**
     * Indicates that the action has been completed.
     */
    case DONE  = 'done';
}
