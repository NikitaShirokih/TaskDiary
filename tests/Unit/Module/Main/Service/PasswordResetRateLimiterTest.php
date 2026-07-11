<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Main\Service;

use App\Module\Main\Service\PasswordResetRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\LimiterStateInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\StorageInterface;

final class PasswordResetRateLimiterTest extends TestCase
{
    public function testAcceptedForgotPasswordRequestDoesNotFail(): void
    {
        $storage = new PasswordResetTrackingStorage();
        $service = new PasswordResetRateLimiter($this->factory('forgot', 1, $storage), $this->factory('reset', 1));

        $service->consumeForgotPassword($this->request(), ' User@Example.COM ');

        self::assertSame('forgot-192.0.2.20:user@example.com', $storage->lastFetchedId);
    }

    public function testExceededForgotPasswordLimitThrows(): void
    {
        $factory = $this->factory('forgot', 1);
        $service = new PasswordResetRateLimiter($factory, $this->factory('reset', 1));
        $service->consumeForgotPassword($this->request(), 'user@example.com');

        $this->expectException(TooManyRequestsHttpException::class);
        $this->expectExceptionMessage('Слишком много запросов восстановления пароля');
        $service->consumeForgotPassword($this->request(), 'user@example.com');
    }

    public function testResetPasswordKeyDoesNotContainRawToken(): void
    {
        $storage = new PasswordResetTrackingStorage();
        $factory = $this->factory('reset', 1, $storage);

        (new PasswordResetRateLimiter($this->factory('forgot', 1), $factory))
            ->consumeResetPassword($this->request(), 'very-secret-raw-token');

        self::assertNotNull($storage->lastFetchedId);
        self::assertStringNotContainsString('very-secret-raw-token', $storage->lastFetchedId);
        self::assertStringContainsString(substr(hash('sha256', 'very-secret-raw-token'), 0, 16), $storage->lastFetchedId);
    }

    private function request(): Request
    {
        return Request::create('/forgot-password', 'POST', server: ['REMOTE_ADDR' => '192.0.2.20']);
    }

    private function factory(string $id, int $limit, ?StorageInterface $storage = null): RateLimiterFactory
    {
        return new RateLimiterFactory([
            'id' => $id, 'policy' => 'fixed_window', 'limit' => $limit, 'interval' => '10 minutes',
        ], $storage ?? new PasswordResetTrackingStorage());
    }
}

final class PasswordResetTrackingStorage implements StorageInterface
{
    public ?string $lastFetchedId = null;

    /** @var array<string, LimiterStateInterface> */
    private array $states = [];

    public function save(LimiterStateInterface $limiterState): void
    {
        $this->states[$limiterState->getId()] = $limiterState;
    }

    public function fetch(string $limiterStateId): ?LimiterStateInterface
    {
        $this->lastFetchedId = $limiterStateId;
        return $this->states[$limiterStateId] ?? null;
    }

    public function delete(string $limiterStateId): void
    {
        unset($this->states[$limiterStateId]);
    }
}
