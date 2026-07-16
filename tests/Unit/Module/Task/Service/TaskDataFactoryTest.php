<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Task\Service;

use App\Module\Task\Enum\TaskPriority;
use App\Module\Task\Enum\TaskStatus;
use App\Module\Task\Service\TaskDataFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TaskDataFactoryTest extends TestCase
{
    public function testValidPayloadCreatesNormalizedTaskData(): void
    {
        $data = (new TaskDataFactory())->fromArray([
            'title' => '  Задача  ',
            'description' => '  Описание  ',
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::InProgress->value,
            'start_time' => '2030-01-01 10:00:00',
            'deadlineAt' => '2030-01-02 12:00:00',
            'category' => ['name' => '  Работа  '],
        ]);

        self::assertSame('Задача', $data->title);
        self::assertSame('Описание', $data->description);
        self::assertSame(TaskPriority::High->value, $data->priority);
        self::assertSame(TaskStatus::InProgress->value, $data->status);
        self::assertSame('2030-01-01', $data->startTime?->format('Y-m-d'));
        self::assertSame('2030-01-02', $data->endTime?->format('Y-m-d'));
        self::assertSame('Работа', $data->categoryName);
    }

    public function testMissingPriorityAndStatusUseCurrentDefaults(): void
    {
        $data = (new TaskDataFactory())->fromArray(['title' => 'Задача']);

        self::assertSame(TaskPriority::Medium->value, $data->priority);
        self::assertSame(TaskStatus::Waiting->value, $data->status);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidPayloadProvider(): iterable
    {
        yield 'empty title' => [['title' => '  ']];
        yield 'invalid priority' => [['title' => 'Задача', 'priority' => 'urgent']];
        yield 'empty priority' => [['title' => 'Задача', 'priority' => '']];
        yield 'invalid status' => [['title' => 'Задача', 'status' => 'unknown']];
        yield 'empty status' => [['title' => 'Задача', 'status' => '']];
    }

    /** @param array<string, mixed> $payload */
    #[DataProvider('invalidPayloadProvider')]
    public function testInvalidPayloadIsRejected(array $payload): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new TaskDataFactory())->fromArray($payload);
    }
}
