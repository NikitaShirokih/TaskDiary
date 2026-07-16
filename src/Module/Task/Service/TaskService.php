<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Main\Entity\User;
use App\Module\Main\Service\AuthenticatedUserProvider;
use App\Module\Task\Dto\TaskData;
use App\Module\Task\Entity\Task;
use App\Module\Task\Event\TaskChangedEvent;
use App\Module\Task\Enum\TaskPriority;
use App\Module\Task\Enum\TaskStatus;
use App\Module\Task\Exception\TaskNotFoundException;
use App\Module\Task\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TaskService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TaskCategoryResolver $taskCategoryResolver,
        private readonly TaskRepository $taskRepository,
        private readonly AuthenticatedUserProvider $authenticatedUserProvider,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function addTask(TaskData $data): Task
    {
        $user = $this->authenticatedUserProvider->getUser();

        $category = $this->taskCategoryResolver->resolveForUser($user, $data->categoryName);
        $priority = TaskPriority::fromInput(
            $data->priority,
            sprintf('Некорректный приоритет: "%s".', $data->priority),
        );
        $status = TaskStatus::fromInput(
            $data->status,
            sprintf('Некорректный статус: "%s".', $data->status),
        );

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

        $this->dispatchTaskChangedForUser($user);

        return $task;
    }

    public function addSubtask(int $parentId, TaskData $data): Task
    {
        $parent = $this->getTaskById($parentId);

        return $this->addSubtaskToTask($parent, $data);
    }

    public function addSubtaskToTask(Task $parent, TaskData $data): Task
    {
        $user = $this->authenticatedUserProvider->getUser();

        $priority = TaskPriority::fromInput(
            $data->priority,
            sprintf('Некорректный приоритет: "%s".', $data->priority),
        );
        $status = TaskStatus::fromInput(
            $data->status,
            sprintf('Некорректный статус: "%s".', $data->status),
        );

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

        $this->dispatchTaskChangedForUser($user);

        return $subtask;
    }

    public function updateTask(int $id, TaskData $data): Task
    {
        $task = $this->getTaskById($id);

        return $this->updateTaskEntity($task, $data);
    }

    public function updateTaskEntity(Task $task, TaskData $data): Task
    {
        $user = $task->getUser();

        $priority = TaskPriority::fromInput(
            $data->priority,
            sprintf('Некорректный приоритет: "%s".', $data->priority),
        );
        $status = TaskStatus::fromInput(
            $data->status,
            sprintf('Некорректный статус: "%s".', $data->status),
        );

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
            $category = $this->taskCategoryResolver->resolveForUser($user, $data->categoryName);

            $task->assignCategory($category);
            $task->schedule($data->startTime, $data->endTime);
        }

        $this->entityManager->flush();

        $this->dispatchTaskChangedForUser($user);

        return $task;
    }

    public function updateStatus(Task $task, TaskStatus $status): Task
    {
        $user = $task->getUser();

        $this->applyStatus($task, $status);

        $this->entityManager->flush();

        $this->dispatchTaskChangedForUser($user);

        return $task;
    }

    public function deleteTask(Task $task): void
    {
        $user = $task->getUser();

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        $this->dispatchTaskChangedForUser($user);
    }

    public function getTaskById(int $id): Task
    {
        return $this->taskRepository->find($id)
            ?? throw new TaskNotFoundException(sprintf('Задача #%d не найдена.', $id));
    }

    private function dispatchTaskChangedForUser(User $user): void
    {
        $userId = $user->getId();

        if ($userId === null) {
            throw new \LogicException('Task owner must have an id.');
        }

        $this->eventDispatcher->dispatch(new TaskChangedEvent($userId));
    }

    private function applyStatus(Task $task, TaskStatus $status): void
    {
        $task->changeStatus($status);
    }
}
