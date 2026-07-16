<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Main\Service;

use App\Module\Main\Entity\User;
use App\Module\Main\Repository\UserRepository;
use App\Module\Main\Security\SecureTokenGenerator;
use App\Module\Main\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EmailVerificationServiceTest extends TestCase
{
    public function testRequestVerificationStoresHashAndReturnsRawToken(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');
        $user = new User();

        $token = $this->service(entityManager: $entityManager)->requestVerification($user);

        self::assertSame(64, strlen($token));
        self::assertNotSame($token, $user->getEmailVerificationTokenHash());
        self::assertSame(hash('sha256', $token), $user->getEmailVerificationTokenHash());
        self::assertNotNull($user->getEmailVerificationTokenExpiresAt());
    }

    public function testVerifyConfirmsUserAndClearsToken(): void
    {
        $user = new User();
        $user->requestEmailVerification(hash('sha256', 'valid'), new \DateTimeImmutable('+1 hour'));
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findOneByEmailVerificationTokenHash')
            ->with(hash('sha256', 'valid'))
            ->willReturn($user);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $result = $this->service($repository, $entityManager)->verify('valid');

        self::assertSame($user, $result);
        self::assertTrue($user->isVerified());
        self::assertNull($user->getEmailVerificationTokenHash());
        self::assertNull($user->getEmailVerificationTokenExpiresAt());
    }

    public function testEmptyTokenIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Некорректная ссылка');
        $this->service()->verify('  ');
    }

    public function testUnknownTokenIsRejected(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findOneByEmailVerificationTokenHash')
            ->with(hash('sha256', 'unknown'))
            ->willReturn(null);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('недействительна');
        $this->service($repository)->verify('unknown');
    }

    public function testExpiredTokenIsRejected(): void
    {
        $user = new User();
        $user->requestEmailVerification(hash('sha256', 'expired'), new \DateTimeImmutable('-1 second'));
        $repository = $this->createStub(UserRepository::class);
        $repository->method('findOneByEmailVerificationTokenHash')->willReturn($user);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('истёк');
        $this->service($repository)->verify('expired');
    }

    private function service(?UserRepository $repository = null, ?EntityManagerInterface $entityManager = null): EmailVerificationService
    {
        return new EmailVerificationService(
            $repository ?? $this->createStub(UserRepository::class),
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            new SecureTokenGenerator(),
        );
    }
}
