<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Entity\Task;

class TaskExportService
{
    /**
     * @return array<string, mixed>
     */
    public function serializeTask(Task $task): array
    {
        $category = [];
        if (null !== $task->getCategory()) {
            $category = [
                'id' => $task->getCategory()->getId(),
                'name' => $task->getCategory()->getName(),
                'color' => $task->getCategory()->getColor(),
            ];
        }

        $subtasks = [];
        foreach ($task->getChildren() as $child) {
            $subtasks[] = $this->serializeTask($child);
        }

        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus()->value,
            'priority' => $task->getPriority()->value,
            'start_time' => $task->getStartTime()?->format('Y-m-d H:i:sP'),
            'end_time' => $task->getEndTime()?->format('Y-m-d H:i:sP'),
            'created_at' => $task->getCreatedAt()?->format('Y-m-d H:i:sP'),
            'is_overdue' => $task->isOverdue(),
            'category' => $category,
            'subtasks' => $subtasks,
        ];
    }

    /**
     * @param array<int, Task> $tasks
     *
     * @return array<int, array<string, mixed>>
     */
    public function exportTasks(Task $task, array $tasks): array
    {
        return array_map(
            fn (Task $task) => $this->serializeTask($task),
            $tasks
        );
    }
}
