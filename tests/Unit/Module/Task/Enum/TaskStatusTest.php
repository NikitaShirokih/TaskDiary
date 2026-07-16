<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Task\Enum;

use App\Module\Task\Enum\TaskStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TaskStatusTest extends TestCase
{
    public function testFromInputReturnsStatusAndTrimsValue(): void
    {
        self::assertSame(TaskStatus::Completed, TaskStatus::fromInput(' completed '));
    }

    public function testFromInputSupportsLegacyInProgressValue(): void
    {
        self::assertSame(TaskStatus::InProgress, TaskStatus::fromInput('inProgress'));
    }

    public function testFromInputRejectsInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Некорректный статус задачи.');

        TaskStatus::fromInput('invalid');
    }

    public function testFromInputRejectsEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TaskStatus::fromInput('   ');
    }
}
