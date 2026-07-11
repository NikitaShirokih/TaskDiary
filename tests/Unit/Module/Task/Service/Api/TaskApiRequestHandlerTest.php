<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Task\Service\Api;

use App\Module\Task\Enum\TaskStatus;
use App\Module\Task\Service\Api\TaskApiRequestHandler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

final class TaskApiRequestHandlerTest extends TestCase
{
    public function testValidCreateJsonReturnsTaskData(): void
    {
        $request = Request::create('/api/tasks', 'POST', [], [], [], [], json_encode([
            'title' => '  Important task ', 'description' => ' Details ', 'priority' => 'high',
            'status' => 'in_progress', 'category' => ['name' => ' Work '], 'deadlineAt' => '2030-01-02T12:00:00+00:00',
        ], JSON_THROW_ON_ERROR));
        $data = (new TaskApiRequestHandler())->handleCreate($request);
        self::assertSame('Important task', $data->title);
        self::assertSame('Details', $data->description);
        self::assertSame('high', $data->priority);
        self::assertSame('in_progress', $data->status);
        self::assertSame('Work', $data->categoryName);
        self::assertSame('2030-01-02', $data->endTime?->format('Y-m-d'));
    }

    #[DataProvider('invalidCreateBodies')]
    public function testInvalidCreateBodyIsRejected(string $body): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TaskApiRequestHandler())->handleCreate(Request::create('/api/tasks', 'POST', [], [], [], [], $body));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidCreateBodies(): iterable
    {
        yield 'empty body' => ['']; yield 'invalid json' => ['{bad']; yield 'empty title' => ['{"title":"  "}'];
    }

    public function testInvalidStatusIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TaskApiRequestHandler())->handleStatus(Request::create('/api/tasks/1/status', 'PATCH', [], [], [], [], '{"status":"unknown"}'));
    }

    public function testValidStatusIsReturned(): void
    {
        $status = (new TaskApiRequestHandler())->handleStatus(Request::create('/api/tasks/1/status', 'PATCH', [], [], [], [], '{"status":"completed"}'));
        self::assertSame(TaskStatus::Completed, $status);
    }
}
