<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Main\Security;

use App\Module\Main\Security\LoginRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterStateInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\StorageInterface;

final class LoginRateLimiterTest extends TestCase
{
    public function testCreatesLimiterUsingIpAndNormalizedUsername(): void
    {
        $storage = new TrackingRateLimiterStorage();
        $factory = new RateLimiterFactory([
            'id' => 'login', 'policy' => 'fixed_window', 'limit' => 5, 'interval' => '1 minute',
        ], $storage);
        $request = Request::create('/login', 'POST', ['_username' => ' User@Example.COM '], server: ['REMOTE_ADDR' => '192.0.2.10']);

        (new LoginRateLimiter($factory))->create($request)->consume();

        self::assertSame('login-192.0.2.10:user@example.com', $storage->lastFetchedId);
    }
}

final class TrackingRateLimiterStorage implements StorageInterface
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
