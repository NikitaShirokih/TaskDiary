<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Module\Task\Dto\TaskData;
use App\Module\Task\Entity\Category;
use App\Module\Task\Entity\Task;
use App\Module\Main\Entity\User;
use App\Module\Task\Enum\TaskPriority;
use App\Module\Task\Enum\TaskStatus;
use App\Module\Task\Exception\TaskNotFoundException;
use App\Module\Task\Repository\CategoryRepository;
use App\Module\Task\Repository\TaskRepository;
use App\Module\Task\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TaskServiceTest extends TestCase
{
    private function makeService(
        ?EntityManagerInterface $entityManager = null,
        ?Security               $security = null,
        ?CategoryRepository     $categoryRepository = null,
        ?TaskRepository         $taskRepository = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): TaskService
    {
        return new TaskService(
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $categoryRepository ?? $this->makeCategoryRepositoryWithDefaultCategory(),
            $taskRepository ?? $this->createStub(TaskRepository::class),
            $security ?? $this->createStub(Security::class),
            $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
        );
    }

    private function makeData(
        string              $title = 'Тестовая задача',
        string              $priority = TaskPriority::Medium->value,
        string              $status = TaskStatus::Waiting->value,
        ?string             $description = null,
        ?\DateTimeImmutable $startTime = null,
        ?\DateTimeImmutable $endTime = null,
        ?int                $categoryId = 1,
    ): TaskData
    {
        return new TaskData(
            title: $title,
            description: $description,
            priority: $priority,
            status: $status,
            startTime: $startTime,
            endTime: $endTime,
            categoryId: $categoryId,
        );
    }

    private function makeCategoryRepositoryWithDefaultCategory(): CategoryRepository
    {
        $category = $this->createStub(Category::class);

        $categoryRepository = $this->createStub(CategoryRepository::class);
        $categoryRepository->method('find')->willReturn($category);

        return $categoryRepository;
    }

    private function makeSecurityWithUser(): Security
    {
        $user = $this->makeUser();
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        return $security;
    }

    private function makeUser(): User
    {
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn(1);

        return $user;
    }

    public function testAddTaskPersistsTaskWithCorrectTitle(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                fn(Task $task) => $task->getTitle() === 'Моя задача'
            ));
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            security: $this->makeSecurityWithUser(),
        );

        $service->addTask($this->makeData(title: 'Моя задача'));
    }

    public function testAddTaskTrimsTitleWhitespace(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                fn(Task $task) => $task->getTitle() === 'Обрезанный заголовок'
            ));
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            security: $this->makeSecurityWithUser(),
        );

        $service->addTask($this->makeData(title: '   Обрезанный заголовок   '));
    }

    public function testAddTaskResolvesCategory(): void
    {
        $category = $this->createStub(Category::class);

        $categoryRepo = $this->createMock(CategoryRepository::class);
        $categoryRepo->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($category);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                fn(Task $task) => $task->getCategory() === $category
            ));
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            security: $this->makeSecurityWithUser(),
            categoryRepository: $categoryRepo,
        );

        $service->addTask($this->makeData(categoryId: 42));
    }

    public function testAddTaskThrowsWhenCategoryNotFound(): void
    {
        $categoryRepo = $this->createStub(CategoryRepository::class);
        $categoryRepo->method('find')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            security: $this->makeSecurityWithUser(),
            categoryRepository: $categoryRepo,
        );

        $this->expectException(\RuntimeException::class);

        $service->addTask($this->makeData(categoryId: 99));
    }

    public function testAddSubtaskPersistsSubtaskWithParent(): void
    {
        $user = $this->makeUser();
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $parent = Task::create(user: $user, title: 'Родитель');

        $taskRepo = $this->createStub(TaskRepository::class);
        $taskRepo->method('find')->willReturn($parent);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                fn(Task $task) => $task->isSubtask() && $task->getParent() === $parent
            ));
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            security: $security,
            taskRepository: $taskRepo,
        );

        $service->addSubtask(1, $this->makeData(title: 'Подзадача'));
    }

    public function testAddSubtaskThrowsWhenParentNotFound(): void
    {
        $taskRepo = $this->createStub(TaskRepository::class);
        $taskRepo->method('find')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');

        $service = $this->makeService(
            entityManager: $em,
            security: $this->makeSecurityWithUser(),
            taskRepository: $taskRepo,
        );

        $this->expectException(TaskNotFoundException::class);

        $service->addSubtask(999, $this->makeData());
    }

    public function testAddSubtaskTrimsTitleWhitespace(): void
    {
        $user = $this->makeUser();
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $parent = Task::create(user: $user, title: 'Родитель');

        $taskRepo = $this->createStub(TaskRepository::class);
        $taskRepo->method('find')->willReturn($parent);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                fn(Task $task) => $task->getTitle() === 'Чистый заголовок'
            ));
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            security: $security,
            taskRepository: $taskRepo,
        );

        $service->addSubtask(1, $this->makeData(title: '  Чистый заголовок  '));
    }

    public function testUpdateTaskRenamesTask(): void
    {
        $user = $this->makeUser();
        $task = Task::create(user: $user, title: 'Старый заголовок');

        $taskRepo = $this->createStub(TaskRepository::class);
        $taskRepo->method('find')->willReturn($task);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            taskRepository: $taskRepo,
        );

        $service->updateTask(1, $this->makeData(title: 'Новый заголовок'));

        $this->assertSame('Новый заголовок', $task->getTitle());
    }

    public function testUpdateTaskChangesPriority(): void
    {
        $user = $this->makeUser();
        $task = Task::create(user: $user, title: 'Задача', priority: TaskPriority::Low);

        $taskRepo = $this->createStub(TaskRepository::class);
        $taskRepo->method('find')->willReturn($task);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            taskRepository: $taskRepo,
        );

        $service->updateTask(1, $this->makeData(priority: 'high'));

        $this->assertSame(TaskPriority::High, $task->getPriority());
    }

    public function testUpdateTaskThrowsWhenTaskNotFound(): void
    {
        $taskRepo = $this->createStub(TaskRepository::class);
        $taskRepo->method('find')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            taskRepository: $taskRepo,
        );

        $this->expectException(TaskNotFoundException::class);

        $service->updateTask(999, $this->makeData());
    }

    public function testUpdateStatusSetsInProgress(): void
    {
        $user = $this->makeUser();
        $task = Task::create(user: $user, title: 'Задача');

        $taskRepo = $this->createStub(TaskRepository::class);
        $taskRepo->method('find')->willReturn($task);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            taskRepository: $taskRepo,
        );

        $service->updateStatus(1, 'in_progress');

        $this->assertSame(TaskStatus::InProgress, $task->getStatus());
    }

    public function testUpdateStatusSetsCompleted(): void
    {
        $user = $this->makeUser();
        $task = Task::create(user: $user, title: 'Задача');

        $taskRepo = $this->createStub(TaskRepository::class);
        $taskRepo->method('find')->willReturn($task);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            taskRepository: $taskRepo,
        );

        $service->updateStatus(1, 'completed');

        $this->assertSame(TaskStatus::Completed, $task->getStatus());
        $this->assertTrue($task->isCompleted());
    }

    public function testUpdateStatusReopensTask(): void
    {
        $user = $this->makeUser();
        $task = Task::create(user: $user, title: 'Задача');
        $task->start();

        $taskRepo = $this->createStub(TaskRepository::class);
        $taskRepo->method('find')->willReturn($task);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            taskRepository: $taskRepo,
        );

        $service->updateStatus(1, 'waiting');

        $this->assertSame(TaskStatus::Waiting, $task->getStatus());
    }

    public function testUpdateStatusThrowsOnInvalidStatus(): void
    {
        $user = $this->makeUser();
        $task = Task::create(user: $user, title: 'Задача');

        $taskRepo = $this->createStub(TaskRepository::class);
        $taskRepo->method('find')->willReturn($task);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            taskRepository: $taskRepo,
        );

        $this->expectException(\InvalidArgumentException::class);

        $service->updateStatus(1, 'несуществующий_статус');
    }

    public function testDeleteTaskCallsRemoveAndFlush(): void
    {
        $user = $this->makeUser();
        $task = Task::create(user: $user, title: 'Задача');

        $taskRepo = $this->createStub(TaskRepository::class);
        $taskRepo->method('find')->willReturn($task);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($task));
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            taskRepository: $taskRepo,
        );

        $service->deleteTask(1);
    }

    public function testDeleteTaskThrowsWhenTaskNotFound(): void
    {
        $taskRepo = $this->createStub(TaskRepository::class);
        $taskRepo->method('find')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('remove');
        $em->expects($this->never())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            taskRepository: $taskRepo,
        );

        $this->expectException(TaskNotFoundException::class);

        $service->deleteTask(999);
    }

    public function testGetTaskByIdReturnsTask(): void
    {
        $user = $this->makeUser();
        $task = Task::create(user: $user, title: 'Задача');

        $taskRepo = $this->createMock(TaskRepository::class);
        $taskRepo->expects($this->once())
            ->method('find')->willReturn($task)
            ->with(1)
            ->willReturn($task);

        $service = $this->makeService(taskRepository: $taskRepo);

        $result = $service->getTaskById(1);

        $this->assertSame($task, $result);
    }

    public function testGetTaskByIdThrowsWhenNotFound(): void
    {
        $taskRepo = $this->createStub(TaskRepository::class);
        $taskRepo->method('find')->willReturn(null);

        $service = $this->makeService(taskRepository: $taskRepo);

        $this->expectException(TaskNotFoundException::class);
        $this->expectExceptionMessage('Задача #42 не найдена.');

        $service->getTaskById(42);
    }

    public function testUpdateStatusThrowsWhenTaskNotFound(): void
    {
        $taskRepo = $this->createMock(TaskRepository::class);
        $taskRepo->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            taskRepository: $taskRepo,
        );

        $this->expectException(TaskNotFoundException::class);

        $service->updateStatus(999, TaskStatus::Completed->value);
    }
}
