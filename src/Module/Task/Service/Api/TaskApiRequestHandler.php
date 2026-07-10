<?php

declare(strict_types=1);

namespace App\Module\Task\Service\Api;

use App\Module\Task\Dto\TaskData;
use App\Module\Task\Enum\TaskPriority;
use App\Module\Task\Enum\TaskStatus;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

final readonly class TaskApiRequestHandler
{
    public function handleCreate(Request $request): TaskData
    {
        return $this->createTaskData($this->decodeJson($request));
    }

    public function handleUpdate(Request $request): TaskData
    {
        return $this->createTaskData($this->decodeJson($request));
    }

    public function handleSubtask(Request $request): TaskData
    {
        return $this->createTaskData($this->decodeJson($request));
    }

    public function handleStatus(Request $request): TaskStatus
    {
        $payload = $this->decodeJson($request);
        $status = trim((string) ($payload['status'] ?? ''));

        if ($status === '') {
            throw new InvalidArgumentException('Не передан новый статус задачи.');
        }

        return TaskStatus::tryFrom($status)
            ?? throw new InvalidArgumentException('Некорректный статус задачи.');
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        $content = trim($request->getContent());

        if ($content === '') {
            throw new InvalidArgumentException('Некорректный JSON-запрос.');
        }

        $payload = json_decode($content, true);

        if (!is_array($payload)) {
            throw new InvalidArgumentException('Некорректный JSON-запрос.');
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createTaskData(array $payload): TaskData
    {
        $title = trim((string) ($payload['title'] ?? ''));

        if ($title === '') {
            throw new InvalidArgumentException('Название задачи не может быть пустым.');
        }

        $priority = $this->resolvePriority(trim((string) ($payload['priority'] ?? TaskPriority::Medium->value)));
        $status = $this->resolveStatus(trim((string) ($payload['status'] ?? TaskStatus::Waiting->value)));
        $description = trim((string) ($payload['description'] ?? ''));

        return new TaskData(
            title: $title,
            description: $description !== '' ? $description : null,
            priority: $priority->value,
            status: $status->value,
            startTime: $this->parseDateTime($payload['startTime'] ?? $payload['start_time'] ?? null),
            endTime: $this->parseDateTime($payload['endTime'] ?? $payload['end_time'] ?? $payload['deadlineAt'] ?? null),
            categoryName: $this->resolveCategoryName($payload),
        );
    }

    private function resolvePriority(string $priority): TaskPriority
    {
        return TaskPriority::tryFrom($priority)
            ?? throw new InvalidArgumentException('Некорректный приоритет задачи.');
    }

    private function resolveStatus(string $status): TaskStatus
    {
        return TaskStatus::tryFrom($status)
            ?? throw new InvalidArgumentException('Некорректный статус задачи.');
    }

    private function parseDateTime(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable((string) $value);
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

        return trim((string) ($payload['categoryName'] ?? $payload['category'] ?? ''));
    }
}
