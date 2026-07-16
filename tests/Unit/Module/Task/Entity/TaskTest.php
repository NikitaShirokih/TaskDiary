<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Task\Entity;

use App\Module\Main\Entity\User;
use App\Module\Task\Entity\Task;
use App\Module\Task\Enum\TaskPriority;
use App\Module\Task\Enum\TaskStatus;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    public function testCreateSubtaskConnectsParentAndChild(): void
    {
        $user = new User();
        $parent = Task::create($user, 'Родительская задача');

        $subtask = Task::createSubtask($parent, $user, 'Подзадача', TaskPriority::High);

        self::assertSame($parent, $subtask->getParent());
        self::assertTrue($parent->getChildren()->contains($subtask));
        self::assertSame($user, $subtask->getUser());
        self::assertSame('Подзадача', $subtask->getTitle());
        self::assertSame(TaskPriority::High, $subtask->getPriority());
    }

    public function testChangeStatusAllowsTransitionsFromCompleted(): void
    {
        $task = Task::create(new User(), 'Задача');

        $task->changeStatus(TaskStatus::Completed);
        self::assertSame(TaskStatus::Completed, $task->getStatus());

        $task->changeStatus(TaskStatus::InProgress);
        self::assertSame(TaskStatus::InProgress, $task->getStatus());

        $task->changeStatus(TaskStatus::Completed);
        $task->changeStatus(TaskStatus::Waiting);
        self::assertSame(TaskStatus::Waiting, $task->getStatus());
    }

    public function testStatusConvenienceMethodsDelegateWithoutRestrictingTransitions(): void
    {
        $task = Task::create(new User(), 'Задача');

        $task->complete();
        $task->start();
        self::assertSame(TaskStatus::InProgress, $task->getStatus());

        $task->reopen();
        self::assertSame(TaskStatus::Waiting, $task->getStatus());
    }
}
