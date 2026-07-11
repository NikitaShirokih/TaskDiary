<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Main\Security;

use App\Module\Main\Entity\User;
use App\Module\Main\Security\VerifiedUserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

final class VerifiedUserCheckerTest extends TestCase
{
    public function testVerifiedUserPassesPostAuthCheck(): void
    {
        $user = new User();
        $user->verifyEmail(new \DateTimeImmutable());

        (new VerifiedUserChecker())->checkPostAuth($user);

        self::assertTrue($user->isVerified());
    }

    public function testUnverifiedUserIsRejected(): void
    {
        $this->expectException(CustomUserMessageAccountStatusException::class);

        (new VerifiedUserChecker())->checkPostAuth(new User());
    }

    public function testForeignUserImplementationIsIgnored(): void
    {
        $user = $this->createStub(UserInterface::class);

        (new VerifiedUserChecker())->checkPostAuth($user);

        self::assertNotInstanceOf(User::class, $user);
    }

    public function testPreAuthCheckDoesNotFail(): void
    {
        $user = $this->createStub(UserInterface::class);

        (new VerifiedUserChecker())->checkPreAuth($user);

        self::assertInstanceOf(UserInterface::class, $user);
    }
}
