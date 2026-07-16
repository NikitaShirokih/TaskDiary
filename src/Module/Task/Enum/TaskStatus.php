<?php

declare(strict_types=1);

namespace App\Module\Task\Enum;

enum TaskStatus: string
{
    case Waiting = 'waiting';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public static function fromInput(
        string $value,
        string $invalidMessage = 'Некорректный статус задачи.',
    ): self {
        $value = trim($value);

        if ('inProgress' === $value) {
            $value = self::InProgress->value;
        }

        return self::tryFrom($value)
            ?? throw new \InvalidArgumentException($invalidMessage);
    }

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
