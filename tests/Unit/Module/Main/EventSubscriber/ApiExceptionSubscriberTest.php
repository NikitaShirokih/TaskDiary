<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Main\EventSubscriber;

use App\Module\Main\EventSubscriber\ApiExceptionSubscriber;
use App\Module\Task\Exception\TaskNotFoundException;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

final class ApiExceptionSubscriberTest extends TestCase
{
    public function testNonApiPathIsNotHandled(): void
    {
        $event = $this->exceptionEvent('/task/1', new InvalidArgumentException('Ошибка'));

        (new ApiExceptionSubscriber())->onKernelException($event);

        self::assertFalse($event->hasResponse());
    }

    public function testUnknownExceptionIsNotHandled(): void
    {
        $event = $this->exceptionEvent('/api/tasks', new RuntimeException('Ошибка сервера'));

        (new ApiExceptionSubscriber())->onKernelException($event);

        self::assertFalse($event->hasResponse());
    }

    /**
     * @return iterable<string, array{Throwable, int}>
     */
    public static function apiExceptionProvider(): iterable
    {
        yield 'task not found' => [new TaskNotFoundException('Задача не найдена.'), 404];
        yield 'invalid argument' => [new InvalidArgumentException('Некорректные данные.'), 400];
        yield 'logic error' => [new LogicException('Некорректная операция.'), 400];
        yield 'access denied' => [new AccessDeniedException('Доступ запрещён.'), 403];
        yield 'authentication required' => [new AuthenticationException('Требуется авторизация.'), 401];
        yield 'rate limit' => [new TooManyRequestsHttpException(null, 'Слишком много запросов.'), 429];
    }

    #[DataProvider('apiExceptionProvider')]
    public function testApiExceptionBecomesJsonError(Throwable $throwable, int $statusCode): void
    {
        $event = $this->exceptionEvent('/api/tasks', $throwable);

        (new ApiExceptionSubscriber())->onKernelException($event);

        self::assertTrue($event->hasResponse());
        $response = $event->getResponse();
        self::assertSame($statusCode, $response->getStatusCode());
        self::assertSame([
            'error' => [
                'message' => $throwable->getMessage(),
                'code' => $statusCode,
            ],
        ], json_decode((string) $response->getContent(), true));
    }

    private function exceptionEvent(string $path, Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create($path),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }
}
