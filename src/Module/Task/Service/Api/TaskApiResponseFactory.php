<?php

declare(strict_types=1);

namespace App\Module\Task\Service\Api;

use App\Module\Task\Entity\Task;
use DateTimeInterface;

final readonly class TaskApiResponseFactory
{
    /**
     * @return array<string, mixed>
     */
    public function task(Task $task): array
    {
        $category = $task->getCategory();
        $parent = $task->getParent();

        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus()->value,
            'priority' => $task->getPriority()->value,
            'category' => $category === null ? null : [
                'id' => $category->getId(),
                'name' => $category->getName(),
            ],
            'parentId' => $parent?->getId(),
            'startTime' => $this->formatDate($task->getStartTime()),
            'endTime' => $this->formatDate($task->getEndTime()),
            'createdAt' => $this->formatDate($task->getCreatedAt()),
            'updatedAt' => $this->formatDate($task->getUpdatedAt()),
        ];
    }

    /**
     * @param iterable<Task> $tasks
     * @return array<int, array<string, mixed>>
     */
    public function tasks(iterable $tasks): array
    {
        $data = [];

        foreach ($tasks as $task) {
            $data[] = $this->task($task);
        }

        return $data;
    }

    /**
     * @return array{error: string}
     */
    public function error(string $message): array
    {
        return ['error' => $message];
    }

    private function formatDate(?DateTimeInterface $dateTime): ?string
    {
        return $dateTime?->format(DateTimeInterface::ATOM);
    }
}
