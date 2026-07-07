<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Task\Dto\TaskData;
use App\Module\Task\Entity\Category;
use App\Module\Task\Entity\Task;
use App\Module\Task\Event\TaskChangedEvent;
use App\Module\Main\Entity\User;
use App\Module\Task\Enum\TaskPriority;
use App\Module\Task\Enum\TaskStatus;
use App\Module\Task\Exception\TaskNotFoundException;
use App\Module\Task\Repository\CategoryRepository;
use App\Module\Task\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TaskService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository,
        private readonly TaskRepository $taskRepository,
        private readonly Security $security,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function addTask(TaskData $data): void
    {
        $user = $this->getAuthenticatedUser();

        $category = $this->resolveOrCreateCategory($user, $data->categoryName);
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

        $this->dispatchTaskChangedForUser($user);
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

        $this->dispatchTaskChangedForUser($user);
    }

    public function updateTask(int $id, TaskData $data): void
    {
        $task = $this->getTaskById($id);
        $user = $task->getUser();

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
            $category = $this->resolveOrCreateCategory($user, $data->categoryName);

            $task->assignCategory($category);
            $task->schedule($data->startTime, $data->endTime);
        }

        $this->entityManager->flush();

        $this->dispatchTaskChangedForUser($user);
    }

    public function updateStatus(int $id, ?string $status): void
    {
        $task = $this->getTaskById($id);
        $user = $task->getUser();
        $taskStatus = $this->resolveStatus((string) $status);

        $this->applyStatus($task, $taskStatus);

        $this->entityManager->flush();

        $this->dispatchTaskChangedForUser($user);
    }

    public function deleteTask(int $id): void
    {
        $task = $this->getTaskById($id);
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

    private function getAuthenticatedUser(): User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('User is not authenticated.');
        }

        return $user;
    }

    private function dispatchTaskChangedForUser(User $user): void
    {
        $userId = $user->getId();

        if ($userId === null) {
            throw new \LogicException('Task owner must have an id.');
        }

        $this->eventDispatcher->dispatch(new TaskChangedEvent($userId));
    }

    private function resolveOrCreateCategory(User $user, string $categoryName): Category
    {
        $name = trim($categoryName);

        if ('' === $name) {
            throw new RuntimeException('Для основной задачи необходимо выбрать категорию.');
        }

        $category = $this->categoryRepository->findOneByUserAndName($user, $name);

        if ($category instanceof Category) {
            return $category;
        }

        $category = new Category($user);
        $category->setName($name);
        $category->setColor('#3498db');
        $category->setIcon(null);
        $category->setDescription(null);

        $this->entityManager->persist($category);

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
