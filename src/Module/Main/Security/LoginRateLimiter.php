<?php

declare(strict_types=1);

namespace App\Module\Main\Security;

use Symfony\Component\HttpFoundation\RateLimiter\AbstractRequestRateLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class LoginRateLimiter extends AbstractRequestRateLimiter
{
    public function __construct(
        private readonly RateLimiterFactory $loginLimiter,
    ) {
    }

    public function create(Request $request): LimiterInterface
    {
        $email = mb_strtolower(trim((string) $request->request->get('_username', '')));
        $ip = $request->getClientIp() ?? 'unknown';

        return $this->loginLimiter->create(sprintf('%s:%s', $ip, $email));
    }

    /** @return list<LimiterInterface> */
    protected function getLimiters(Request $request): array
    {
        return [$this->create($request)];
    }
}
