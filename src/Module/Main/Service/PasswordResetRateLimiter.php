<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class PasswordResetRateLimiter
{
    public function __construct(
        private RateLimiterFactory $forgotPasswordLimiter,
        private RateLimiterFactory $resetPasswordLimiter,
    ) {
    }

    public function consumeForgotPassword(Request $request, string $email): void
    {
        $ip = $request->getClientIp() ?? 'unknown';
        $email = mb_strtolower(trim($email));
        $limit = $this->forgotPasswordLimiter->create(sprintf('%s:%s', $ip, $email))->consume();

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException(
                null,
                'Слишком много запросов восстановления пароля. Попробуйте позже.',
            );
        }
    }

    public function consumeResetPassword(Request $request, string $token): void
    {
        $ip = $request->getClientIp() ?? 'unknown';
        $tokenPart = substr(hash('sha256', trim($token)), 0, 16);
        $limit = $this->resetPasswordLimiter->create(sprintf('%s:%s', $ip, $tokenPart))->consume();

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException(
                null,
                'Слишком много попыток смены пароля. Попробуйте позже.',
            );
        }
    }
}
