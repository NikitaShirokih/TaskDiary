<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Task\Dto\TaskData;
use App\Module\Task\Enum\TaskPriority;
use App\Module\Task\Enum\TaskStatus;
use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

final readonly class TaskDataFactory
{
    /**
     * @param array<string, mixed> $payload
     */
    public function fromArray(
        array $payload,
        string $emptyTitleMessage = 'Название задачи не может быть пустым.',
    ): TaskData {
        $title = trim((string) ($payload['title'] ?? ''));

        if ('' === $title) {
            throw new InvalidArgumentException($emptyTitleMessage);
        }

        $description = trim((string) ($payload['description'] ?? ''));
        $priority = TaskPriority::fromInput((string) ($payload['priority'] ?? TaskPriority::Medium->value));
        $status = TaskStatus::fromInput((string) ($payload['status'] ?? TaskStatus::Waiting->value));

        return new TaskData(
            title: $title,
            description: '' !== $description ? $description : null,
            priority: $priority->value,
            status: $status->value,
            startTime: $this->parseDateTime($payload['startTime'] ?? $payload['start_time'] ?? null),
            endTime: $this->parseDateTime(
                $payload['endTime'] ?? $payload['end_time'] ?? $payload['deadlineAt'] ?? null,
            ),
            categoryName: $this->resolveCategoryName($payload),
        );
    }

    private function parseDateTime(mixed $value): ?DateTimeImmutable
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

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveCategoryName(array $payload): string
    {
        $category = $payload['category'] ?? null;

        if (is_array($category)) {
            return trim((string) ($category['name'] ?? ''));
        }

        return trim((string) ($payload['categoryName'] ?? $category ?? ''));
    }
}
