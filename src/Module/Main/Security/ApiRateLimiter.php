<?php

declare(strict_types=1);

namespace App\Module\Main\Security;

use App\Module\Main\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class ApiRateLimiter
{
    public function __construct(
        private RateLimiterFactory $apiLimiter,
        private Security $security,
    ) {
    }

    public function consume(Request $request): void
    {
        $user = $this->security->getUser();
        $key = $user instanceof User && $user->getId() !== null
            ? 'user_'.$user->getId()
            : 'ip_'.($request->getClientIp() ?? 'unknown');

        if (!$this->apiLimiter->create($key)->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(
                null,
                'Слишком много API-запросов. Попробуйте позже.',
            );
        }
    }
}
