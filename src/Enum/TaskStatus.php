<?php

declare(strict_types=1);

namespace App\Enum;

enum TaskStatus: string
{
    case Waiting = 'waiting';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Ожидание',
            self::InProgress => 'В работе',
            self::Completed => 'Завершено',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Waiting => 'bg-secondary',
            self::InProgress => 'bg-warning text-dark',
            self::Completed => 'bg-success',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases(),
        );
    }
}
