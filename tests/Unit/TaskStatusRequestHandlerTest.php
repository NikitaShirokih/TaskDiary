<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Module\Task\Enum\TaskStatus;
use App\Module\Task\Service\TaskStatusRequestHandler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class TaskStatusRequestHandlerTest extends TestCase
{
    public function testHandleReturnsTaskStatus(): void
    {
        $handler = new TaskStatusRequestHandler();
        $request = new Request(request: ['status' => ' completed ']);

        $this->assertSame(TaskStatus::Completed, $handler->handle($request));
    }

    public function testHandleThrowsWhenStatusIsMissing(): void
    {
        $handler = new TaskStatusRequestHandler();
        $request = new Request();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Не передан новый статус задачи.');

        $handler->handle($request);
    }

    public function testHandleThrowsWhenStatusIsInvalid(): void
    {
        $handler = new TaskStatusRequestHandler();
        $request = new Request(request: ['status' => 'unknown']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Некорректный статус задачи.');

        $handler->handle($request);
    }
}
