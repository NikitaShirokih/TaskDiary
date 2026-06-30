<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Module\Task\Entity\Task;
use App\Module\Main\Entity\User;
use App\Module\Task\Service\DashboardStatsQueryService;
use App\Module\Task\Enum\TaskPriority;
use App\Module\Task\Enum\TaskStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TaskRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private DashboardStatsQueryService $dashboardStatsQueryService;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->dashboardStatsQueryService = $container->get(DashboardStatsQueryService::class);

        $this->em->createQuery('DELETE FROM App\Module\Task\Entity\Task t')->execute();
        $this->em->createQuery('DELETE FROM App\Module\Main\Entity\User u')->execute();

        $this->em->clear();
    }

    protected function tearDown(): void
    {
        $this->em->close();

        parent::tearDown();
    }

    public function testCountActive(): void
    {
        $user = $this->createUser('qwerty@example.com');
        $otherUser = $this->createUser('qwer@example.com');

        $this->createTask(
            user: $user,
            title: 'Waiting task',
            status: TaskStatus::Waiting,
        );
        $this->createTask(
            user: $user,
            title: 'InProgress task',
            status: TaskStatus::InProgress,
        );
        $this->createTask(
            user: $user,
            title: 'Completed task',
            status: TaskStatus::Completed,
        );

        $this->createTask(
            user: $otherUser,
            title: 'Other user waiting task',
            status: TaskStatus::Waiting,
        );

        $this->em->flush();

        $count = $this->dashboardStatsQueryService->countActive($user);

        self::assertSame(2, $count);
    }

    private function createUser(string $email): User
    {
        $user = new User();

        $user->setEmail($email);
        $user->setPassword('test-password-hash');

        $this->em->persist($user);

        return $user;
    }

    private function createTask(
        User $user,
        string $title,
        TaskStatus $status = TaskStatus::Waiting,
        TaskPriority $priority = TaskPriority::Medium,
    ): Task {
        $task = Task::create(
            user: $user,
            title: $title,
            priority: $priority,
        );

        if (TaskStatus::InProgress === $status) {
            $task->start();
        }

        if (TaskStatus::Completed === $status) {
            $task->complete();
        }

        $this->em->persist($task);

        return $task;
    }

}
