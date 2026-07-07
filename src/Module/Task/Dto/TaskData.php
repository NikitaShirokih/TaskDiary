<?php

declare(strict_types=1);

namespace App\Module\Task\Dto;

use App\Module\Task\Enum\TaskPriority;
use App\Module\Task\Enum\TaskStatus;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;

final readonly class TaskData
{
    public function __construct(
        #[Assert\NotBlank(message: 'Название задачи обязательно.')]
        #[Assert\Length(max: 255, maxMessage: 'Название задачи не должно быть длиннее 255 символов.')]
        public string $title,

        public ?string $description,

        #[Assert\NotBlank(message: 'Приоритет задачи обязателен.')]
        public string $priority,

        #[Assert\NotBlank(message: 'Статус задачи обязателен.')]
        public string $status,

        public ?DateTimeImmutable $startTime,

        public ?DateTimeImmutable $endTime,

        public string $categoryName,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $title = trim((string) $request->request->get('title', ''));

        if ('' === $title) {
            throw new InvalidArgumentException('Название задачи обязательно.');
        }

        $description = trim((string) $request->request->get('description', ''));

        $priority = trim((string) $request->request->get('priority', TaskPriority::Medium->value));
        $priority = self::normalizePriority($priority);

        $status = trim((string) $request->request->get('status', TaskStatus::Waiting->value));
        $status = self::normalizeStatus($status);

        $startTime = self::parseDateTime($request->request->get('start_time'));
        $endTime = self::parseDateTime($request->request->get('end_time'));

        $categoryName = trim((string) $request->request->get('categoryName', ''));

        return new self(
            title: $title,
            description: '' !== $description ? $description : null,
            priority: $priority,
            status: $status,
            startTime: $startTime,
            endTime: $endTime,
            categoryName: $categoryName,
        );
    }

    private static function normalizePriority(string $priority): string
    {
        return match ($priority) {
            'low' => TaskPriority::Low->value,
            'medium' => TaskPriority::Medium->value,
            'high' => TaskPriority::High->value,
            default => throw new InvalidArgumentException('Некорректный приоритет задачи.'),
        };
    }

    private static function normalizeStatus(string $status): string
    {
        return match ($status) {
            'waiting' => TaskStatus::Waiting->value,
            'in_progress', 'inProgress' => TaskStatus::InProgress->value,
            'completed' => TaskStatus::Completed->value,
            default => throw new InvalidArgumentException('Некорректный статус задачи.'),
        };
    }

    private static function parseDateTime(mixed $value): ?DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable((string) $value);
        } catch (Throwable) {
            throw new InvalidArgumentException('Некорректный формат даты.');
        }
    }
}
