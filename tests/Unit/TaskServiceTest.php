<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Module\Main\Entity\User;
use App\Module\Main\Service\AuthenticatedUserProvider;
use App\Module\Task\Dto\TaskData;
use App\Module\Task\Entity\Category;
use App\Module\Task\Entity\Task;
use App\Module\Task\Enum\TaskPriority;
use App\Module\Task\Enum\TaskStatus;
use App\Module\Task\Exception\TaskNotFoundException;
use App\Module\Task\Repository\CategoryRepository;
use App\Module\Task\Repository\TaskRepository;
use App\Module\Task\Service\TaskCategoryResolver;
use App\Module\Task\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TaskServiceTest extends TestCase
{
    private function makeService(
        ?EntityManagerInterface $entityManager = null,
        ?AuthenticatedUserProvider $authenticatedUserProvider = null,
        ?CategoryRepository     $categoryRepository = null,
        ?TaskCategoryResolver   $taskCategoryResolver = null,
        ?TaskRepository         $taskRepository = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): TaskService
    {
        $entityManager ??= $this->createStub(EntityManagerInterface::class);
        $categoryRepository ??= $this->makeCategoryRepositoryWithDefaultCategory();

        return new TaskService(
            $entityManager,
            $taskCategoryResolver ?? new TaskCategoryResolver($categoryRepository, $entityManager),
            $taskRepository ?? $this->createStub(TaskRepository::class),
            $authenticatedUserProvider ?? $this->makeAuthenticatedUserProvider(),
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
        string              $categoryName = 'Работа',
    ): TaskData
    {
        return new TaskData(
            title: $title,
            description: $description,
            priority: $priority,
            status: $status,
            startTime: $startTime,
            endTime: $endTime,
            categoryName: $categoryName,
        );
    }

    private function makeCategoryRepositoryWithDefaultCategory(): CategoryRepository
    {
        $category = $this->createStub(Category::class);

        $categoryRepository = $this->createStub(CategoryRepository::class);
        $categoryRepository->method('findOneByUserAndName')->willReturn($category);

        return $categoryRepository;
    }

    private function makeAuthenticatedUserProvider(?User $user = null): AuthenticatedUserProvider
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user ?? $this->makeUser());

        return new AuthenticatedUserProvider($security);
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
            authenticatedUserProvider: $this->makeAuthenticatedUserProvider(),
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
            authenticatedUserProvider: $this->makeAuthenticatedUserProvider(),
        );

        $service->addTask($this->makeData(title: '   Обрезанный заголовок   '));
    }

    public function testAddTaskResolvesCategory(): void
    {
        $user = $this->makeUser();
        $category = $this->createStub(Category::class);

        $categoryRepo = $this->createMock(CategoryRepository::class);
        $categoryRepo->expects($this->once())
            ->method('findOneByUserAndName')
            ->with($user, 'Работа')
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
            authenticatedUserProvider: $this->makeAuthenticatedUserProvider($user),
            categoryRepository: $categoryRepo,
        );

        $service->addTask($this->makeData(categoryName: 'Работа'));
    }

    public function testAddTaskCreatesCategoryWhenCategoryNotFound(): void
    {
        $categoryRepo = $this->createStub(CategoryRepository::class);
        $categoryRepo->method('findOneByUserAndName')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))
            ->method('persist')
            ->with($this->logicalOr(
                $this->isInstanceOf(Category::class),
                $this->isInstanceOf(Task::class),
            ));
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            authenticatedUserProvider: $this->makeAuthenticatedUserProvider(),
            categoryRepository: $categoryRepo,
        );

        $service->addTask($this->makeData(categoryName: 'Новая категория'));
    }

    public function testAddTaskThrowsWhenCategoryNameIsEmpty(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
            authenticatedUserProvider: $this->makeAuthenticatedUserProvider(),
        );

        $this->expectException(\RuntimeException::class);

        $service->addTask($this->makeData(categoryName: '   '));
    }

    public function testAddSubtaskPersistsSubtaskWithParent(): void
    {
        $user = $this->makeUser();
        $authenticatedUserProvider = $this->makeAuthenticatedUserProvider($user);

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
            authenticatedUserProvider: $authenticatedUserProvider,
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
            authenticatedUserProvider: $this->makeAuthenticatedUserProvider(),
            taskRepository: $taskRepo,
        );

        $this->expectException(TaskNotFoundException::class);

        $service->addSubtask(999, $this->makeData());
    }

    public function testAddSubtaskTrimsTitleWhitespace(): void
    {
        $user = $this->makeUser();
        $authenticatedUserProvider = $this->makeAuthenticatedUserProvider($user);

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
            authenticatedUserProvider: $authenticatedUserProvider,
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

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
        );

        $service->updateStatus($task, TaskStatus::InProgress);

        $this->assertSame(TaskStatus::InProgress, $task->getStatus());
    }

    public function testUpdateStatusSetsCompleted(): void
    {
        $user = $this->makeUser();
        $task = Task::create(user: $user, title: 'Задача');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
        );

        $service->updateStatus($task, TaskStatus::Completed);

        $this->assertSame(TaskStatus::Completed, $task->getStatus());
        $this->assertTrue($task->isCompleted());
    }

    public function testUpdateStatusReopensTask(): void
    {
        $user = $this->makeUser();
        $task = Task::create(user: $user, title: 'Задача');
        $task->start();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
        );

        $service->updateStatus($task, TaskStatus::Waiting);

        $this->assertSame(TaskStatus::Waiting, $task->getStatus());
    }

    public function testDeleteTaskCallsRemoveAndFlush(): void
    {
        $user = $this->makeUser();
        $task = Task::create(user: $user, title: 'Задача');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($task));
        $em->expects($this->once())->method('flush');

        $service = $this->makeService(
            entityManager: $em,
        );

        $service->deleteTask($task);
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

}
