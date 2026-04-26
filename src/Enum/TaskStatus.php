<?php

declare(strict_types=1);

namespace App\Enum;

enum TaskStatus: string
{
    case Waiting = 'waiting';
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
