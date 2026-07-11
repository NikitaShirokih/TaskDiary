<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Task\Service\Api;

use App\Module\Main\Entity\User;
use App\Module\Task\Entity\Task;
use App\Module\Task\Service\Api\TaskApiResponseFactory;
use PHPUnit\Framework\TestCase;

final class TaskApiResponseFactoryTest extends TestCase
{
    public function testErrorUsesStandardFormat(): void
    {
        self::assertSame(['error' => ['message' => 'Ошибка', 'code' => 400]], (new TaskApiResponseFactory())->error('Ошибка', 400));
    }

    public function testTaskContainsPublicFieldsWithoutUserData(): void
    {
        $user = (new User())->setEmail('private@example.com')->setPassword('secret-hash');
        $response = (new TaskApiResponseFactory())->task(Task::create($user, 'Public title'));
        self::assertArrayHasKey('id', $response);
        self::assertSame('Public title', $response['title']);
        self::assertSame('waiting', $response['status']);
        self::assertArrayNotHasKey('user', $response);
        self::assertStringNotContainsString('secret-hash', json_encode($response, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('private@example.com', json_encode($response, JSON_THROW_ON_ERROR));
    }

    public function testTasksReturnsCurrentPlainListFormat(): void
    {
        $user = new User();
        $tasks = (new TaskApiResponseFactory())->tasks([Task::create($user, 'One'), Task::create($user, 'Two')]);
        self::assertCount(2, $tasks);
        self::assertSame(['One', 'Two'], array_column($tasks, 'title'));
    }
}
