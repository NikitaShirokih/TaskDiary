<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Task\Enum;

use App\Module\Task\Enum\TaskPriority;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TaskPriorityTest extends TestCase
{
    public function testFromInputReturnsPriorityAndTrimsValue(): void
    {
        self::assertSame(TaskPriority::High, TaskPriority::fromInput(' high '));
    }

    public function testFromInputRejectsInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Некорректный приоритет задачи.');

        TaskPriority::fromInput('invalid');
    }

    public function testFromInputRejectsEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TaskPriority::fromInput('   ');
    }
}
