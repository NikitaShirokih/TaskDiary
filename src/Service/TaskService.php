<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\TaskData;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Exception\TaskNotFoundException;
use App\Repository\CategoryRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class TaskService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository,
        private readonly TaskRepository $taskRepository,
        private readonly Security $security,
    ) {
    }

    public function addTask(TaskData $data): void
    {
        $user = $this->security->getUser();
        assert($user instanceof User); // ← фикс

        $category = $this->resolveCategory($data->categoryId);
        $priority = TaskPriority::from((string) $data->priority);

        $task = Task::create(
            user: $user,
            title: trim((string) $data->title),
            priority: $priority,
            category: $category,
        );

        $this->applyScheduleAndDescription($task, $data);
        $this->applyStatus($task, $data->status);

        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    public function addSubtask(int $parentId, TaskData $data): void
    {
        $parent = $this->getTaskById($parentId);
        $user = $this->security->getUser();
        assert($user instanceof User); // ← фикс

        $priority = TaskPriority::from((string) $data->priority);

        $subtask = Task::createSubtask(
            parent: $parent,
            user: $user,
            title: trim((string) $data->title),
            priority: $priority,
        );

        $this->applyScheduleAndDescription($subtask, $data);

        $this->entityManager->persist($subtask);
        $this->entityManager->flush();
    }

    public function updateTask(int $id, TaskData $data): void
    {
        $task = $this->getTaskById($id);
        $category = $this->resolveCategory($data->categoryId);

        $task->rename(trim((string) $data->title));
        $task->describe($data->description);
        $task->changePriority(TaskPriority::from((string) $data->priority));
        $task->assignCategory($category);

        $this->applyScheduleAndDescription($task, $data);
        $this->applyStatus($task, $data->status);

        $this->entityManager->flush();
    }

    public function updateStatus(int $id, ?string $status): void
    {
        $task = $this->getTaskById($id);
        $taskStatus = TaskStatus::tryFrom((string) $status)
            ?? throw new \InvalidArgumentException(sprintf('Некорректный статус: "%s".', $status));

        match ($taskStatus) {
            TaskStatus::InProgress => $task->start(),
            TaskStatus::Completed => $task->complete(),
            TaskStatus::Waiting => $task->reopen(),
        };

        $this->entityManager->flush();
    }

    public function deleteTask(int $id): void
    {
        $task = $this->getTaskById($id);

        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }

    public function getTaskById(int $id): Task
    {
        return $this->taskRepository->find($id)
            ?? throw new TaskNotFoundException(sprintf('Задача #%d не найдена.', $id));
    }

    private function resolveCategory(?int $categoryId): ?\App\Entity\Category
    {
        if (null === $categoryId) {
            return null;
        }

        return $this->categoryRepository->find($categoryId)
            ?? throw new \RuntimeException(sprintf('Категория #%d не найдена.', $categoryId));
    }

    private function applyScheduleAndDescription(Task $task, TaskData $data): void
    {
        $task->describe($data->description);
        $task->schedule($data->startTime, $data->endTime);
    }

    private function applyStatus(Task $task, mixed $status): void
    {
        $taskStatus = TaskStatus::tryFrom((string) $status);

        if (null === $taskStatus) {
            return;
        }

        match ($taskStatus) {
            TaskStatus::InProgress => $task->start(),
            TaskStatus::Completed => $task->complete(),
            TaskStatus::Waiting => $task->reopen(),
        };
    }
}
