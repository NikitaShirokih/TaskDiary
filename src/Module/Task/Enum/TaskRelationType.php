<?php

declare(strict_types=1);

namespace App\Module\Task\Enum;

enum TaskRelationType: string
{
    case Root = 'root';
    case Parent = 'parent';
    case Child = 'child';
    case Related = 'related';

    public function label(): string
    {
        return match ($this) {
            self::Root => 'Текущая задача',
            self::Parent => 'Родительская задача',
            self::Child => 'Подзадача',
            self::Related => 'Связанная задача',
        };
    }
}
