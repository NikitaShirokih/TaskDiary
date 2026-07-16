<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Main\Entity;

use App\Module\Main\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testEmailVerificationStoresHashAndClearsItAfterVerification(): void
    {
        $rawToken = 'raw-email-token';
        $tokenHash = hash('sha256', $rawToken);
        $user = new User();

        $user->requestEmailVerification($tokenHash, new \DateTimeImmutable('+1 hour'));

        self::assertNotSame($rawToken, $user->getEmailVerificationTokenHash());
        self::assertSame($tokenHash, $user->getEmailVerificationTokenHash());

        $user->verifyEmail(new \DateTimeImmutable());

        self::assertTrue($user->isVerified());
        self::assertNull($user->getEmailVerificationTokenHash());
        self::assertNull($user->getEmailVerificationTokenExpiresAt());
    }

    public function testPasswordResetStoresHashAndClearsIt(): void
    {
        $rawToken = 'raw-reset-token';
        $tokenHash = hash('sha256', $rawToken);
        $user = new User();

        $user->requestPasswordReset($tokenHash, new \DateTimeImmutable('+1 hour'));

        self::assertNotSame($rawToken, $user->getPasswordResetTokenHash());
        self::assertSame($tokenHash, $user->getPasswordResetTokenHash());

        $user->clearPasswordResetToken();

        self::assertNull($user->getPasswordResetTokenHash());
        self::assertNull($user->getPasswordResetTokenExpiresAt());
    }
}
