<?php

declare(strict_types=1);

namespace App\Module\Task\Enum;

enum TaskRights: string
{
    case VIEW = 'TASK_VIEW';
    case EDIT = 'TASK_EDIT';
    case DELETE = 'TASK_DELETE';
}
