<?php

declare(strict_types=1);

namespace App\Enum;

enum TaskRights: string
{
    case OWNER = 'TASK_OWNER';
    case ADMIN = 'TASK_ADMIN';
}
