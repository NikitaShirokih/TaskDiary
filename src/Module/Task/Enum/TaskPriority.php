<?php

declare(strict_types=1);

namespace App\Module\Task\Enum;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
