<?php

declare(strict_types=1);

namespace App\Module\Task\Enum;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public static function fromInput(
        string $value,
        string $invalidMessage = 'Некорректный приоритет задачи.',
    ): self {
        return self::tryFrom(trim($value))
            ?? throw new \InvalidArgumentException($invalidMessage);
    }
}
