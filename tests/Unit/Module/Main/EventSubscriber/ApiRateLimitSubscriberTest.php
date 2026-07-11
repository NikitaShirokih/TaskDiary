<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Main\EventSubscriber;

use App\Module\Main\EventSubscriber\ApiRateLimitSubscriber;
use App\Module\Main\Security\ApiRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class ApiRateLimitSubscriberTest extends TestCase
{
    public function testNonApiPathDoesNotConsumeLimit(): void
    {
        $limiter = $this->limiter(1);
        $subscriber = new ApiRateLimitSubscriber($limiter);
        $subscriber->onKernelRequest($this->requestEvent('/dashboard'));

        $subscriber->onKernelRequest($this->requestEvent('/api/tasks'));

        $this->expectException(TooManyRequestsHttpException::class);
        $subscriber->onKernelRequest($this->requestEvent('/api/tasks'));
    }

    public function testApiPathConsumesLimit(): void
    {
        $subscriber = new ApiRateLimitSubscriber($this->limiter(1));
        $subscriber->onKernelRequest($this->requestEvent('/api/tasks'));

        $this->expectException(TooManyRequestsHttpException::class);
        $subscriber->onKernelRequest($this->requestEvent('/api/tasks'));
    }

    public function testApiRateLimitExceptionBecomesJson429(): void
    {
        $subscriber = new ApiRateLimitSubscriber($this->limiter(1));
        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/api/tasks'),
            HttpKernelInterface::MAIN_REQUEST,
            new TooManyRequestsHttpException(null, 'Слишком много API-запросов. Попробуйте позже.'),
        );

        $subscriber->onKernelException($event);

        self::assertTrue($event->hasResponse());
        $response = $event->getResponse();
        self::assertSame(429, $response->getStatusCode());
        self::assertSame([
            'error' => ['message' => 'Слишком много API-запросов. Попробуйте позже.', 'code' => 429],
        ], json_decode((string) $response->getContent(), true));
    }

    private function limiter(int $limit): ApiRateLimiter
    {
        $factory = new RateLimiterFactory([
            'id' => 'api', 'policy' => 'fixed_window', 'limit' => $limit, 'interval' => '1 minute',
        ], new InMemoryStorage());
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        return new ApiRateLimiter($factory, $security);
    }

    private function requestEvent(string $path): RequestEvent
    {
        return new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create($path, server: ['REMOTE_ADDR' => '192.0.2.30']),
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}
