<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\TaskData;
use App\Entity\Category;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Exception\TaskNotFoundException;
use App\Repository\CategoryRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
        $user = $this->getAuthenticatedUser();

        $category = $this->resolveRequiredCategory($data->categoryId);
        $priority = $this->resolvePriority($data->priority);
        $status = $this->resolveStatus($data->status);

        $task = Task::create(
            user: $user,
            title: trim($data->title),
            priority: $priority,
            category: $category,
        );

        $task->describe($data->description);
        $task->schedule($data->startTime, $data->endTime);
        $this->applyStatus($task, $status);

        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    public function addSubtask(int $parentId, TaskData $data): void
    {
        $parent = $this->getTaskById($parentId);
        $user = $this->getAuthenticatedUser();

        $priority = $this->resolvePriority($data->priority);
        $status = $this->resolveStatus($data->status);

        $subtask = Task::createSubtask(
            parent: $parent,
            user: $user,
            title: trim($data->title),
            priority: $priority,
        );

        $subtask->describe($data->description);

        /*
         * Подзадача НЕ получает свою категорию.
         * Категория принадлежит основной задаче.
         *
         * Если в сущности есть nullable category, оставляем её null.
         * Если подзадача раньше случайно получила категорию через старую логику,
         * при редактировании она будет очищена в updateTask().
         */
        $this->applyStatus($subtask, $status);

        $this->entityManager->persist($subtask);
        $this->entityManager->flush();
    }

    public function updateTask(int $id, TaskData $data): void
    {
        $task = $this->getTaskById($id);

        $priority = $this->resolvePriority($data->priority);
        $status = $this->resolveStatus($data->status);

        $task->rename(trim($data->title));
        $task->describe($data->description);
        $task->changePriority($priority);
        $this->applyStatus($task, $status);

        if ($task->isSubtask()) {
            /*
             * Подзадача не должна иметь собственную категорию.
             * Категория берётся концептуально от основной задачи.
             */
            $task->assignCategory(null);
        } else {
            $category = $this->resolveRequiredCategory($data->categoryId);

            $task->assignCategory($category);
            $task->schedule($data->startTime, $data->endTime);
        }

        $this->entityManager->flush();
    }

    public function updateStatus(int $id, ?string $status): void
    {
        $task = $this->getTaskById($id);
        $taskStatus = $this->resolveStatus((string) $status);

        $this->applyStatus($task, $taskStatus);

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

    /**
     * @return array<int, Task>
     */
    public function getTaskWithDescendantsForExport(int $id): array
    {
        return $this->taskRepository->findTaskWithDescendantsForExport($id);
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('User is not authenticated.');
        }

        return $user;
    }

    private function resolveRequiredCategory(?int $categoryId): Category
    {
        if (null === $categoryId) {
            throw new RuntimeException('Для основной задачи необходимо выбрать категорию.');
        }

        $category = $this->categoryRepository->find($categoryId);

        if (!$category instanceof Category) {
            throw new RuntimeException(sprintf('Категория #%d не найдена.', $categoryId));
        }

        return $category;
    }

    private function resolvePriority(string $priority): TaskPriority
    {
        return TaskPriority::tryFrom($priority)
            ?? throw new InvalidArgumentException(sprintf('Некорректный приоритет: "%s".', $priority));
    }

    private function resolveStatus(string $status): TaskStatus
    {
        return TaskStatus::tryFrom($status)
            ?? throw new InvalidArgumentException(sprintf('Некорректный статус: "%s".', $status));
    }

    private function applyStatus(Task $task, TaskStatus $status): void
    {
        match ($status) {
            TaskStatus::InProgress => $task->start(),
            TaskStatus::Completed => $task->complete(),
            TaskStatus::Waiting => $task->reopen(),
        };
    }
}
