<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Main\Service;

use App\Module\Main\Dto\ResetPasswordData;
use App\Module\Main\Entity\User;
use App\Module\Main\Exception\PasswordResetException;
use App\Module\Main\Repository\UserRepository;
use App\Module\Main\Service\PasswordResetService;
use App\Module\Main\Service\UserMailer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordResetServiceTest extends TestCase
{
    public function testRequestResetStoresHashAndEmailsRawTokenUrl(): void
    {
        $user = $this->verifiedUser();
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())->method('findOneByEmail')->with('user@example.com')->willReturn($user);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send')->with($this->callback(function (Email $email) use ($user): bool {
            preg_match('#https://taskdiary\.test/reset-password/([a-f0-9]{64})#', $email->getHtmlBody() ?? '', $matches);
            return isset($matches[1]) && hash('sha256', $matches[1]) === $user->getPasswordResetTokenHash();
        }));

        $this->service($repository, $entityManager, mailer: $mailer)->requestReset(' USER@example.com ');

        self::assertSame(64, strlen((string) $user->getPasswordResetTokenHash()));
        self::assertNotNull($user->getPasswordResetTokenExpiresAt());
    }

    public function testUnknownEmailDoesNothing(): void
    {
        $repository = $this->createStub(UserRepository::class);
        $repository->method('findOneByEmail')->willReturn(null);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');
        $this->service($repository, $entityManager, mailer: $mailer)->requestReset('missing@example.com');
    }

    public function testUnverifiedUserDoesNotReceiveEmail(): void
    {
        $repository = $this->createStub(UserRepository::class);
        $repository->method('findOneByEmail')->willReturn(new User());
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');
        $this->service($repository, $entityManager, mailer: $mailer)->requestReset('user@example.com');
    }

    public function testResetPasswordWithValidTokenChangesPasswordAndClearsToken(): void
    {
        $user = $this->verifiedUser();
        $user->requestPasswordReset(hash('sha256', 'raw-token'), new \DateTimeImmutable('+1 hour'));
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())->method('findOneByPasswordResetTokenHash')->with(hash('sha256', 'raw-token'))->willReturn($user);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->once())->method('hashPassword')->with($user, 'new-password')->willReturn('new-hash');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');
        $data = new ResetPasswordData();
        $data->plainPassword = 'new-password';

        $this->service($repository, $entityManager, $hasher)->resetPassword('raw-token', $data);

        self::assertSame('new-hash', $user->getPassword());
        self::assertNull($user->getPasswordResetTokenHash());
        self::assertNull($user->getPasswordResetTokenExpiresAt());
    }

    public function testEmptyTokenIsRejected(): void
    {
        $this->expectException(PasswordResetException::class);
        $this->service()->assertTokenCanBeUsed(' ');
    }

    public function testUnknownTokenIsRejected(): void
    {
        $repository = $this->createStub(UserRepository::class);
        $repository->method('findOneByPasswordResetTokenHash')->willReturn(null);
        $this->expectException(PasswordResetException::class);
        $this->service($repository)->assertTokenCanBeUsed('unknown');
    }

    public function testExpiredTokenIsRejected(): void
    {
        $user = $this->verifiedUser();
        $user->requestPasswordReset(hash('sha256', 'expired'), new \DateTimeImmutable('-1 second'));
        $repository = $this->createStub(UserRepository::class);
        $repository->method('findOneByPasswordResetTokenHash')->willReturn($user);
        $this->expectException(PasswordResetException::class);
        $this->expectExceptionMessage('истёк');
        $this->service($repository)->assertTokenCanBeUsed('expired');
    }

    private function verifiedUser(): User
    {
        $user = (new User())->setEmail('user@example.com');
        $user->verifyEmail(new \DateTimeImmutable());
        return $user;
    }

    private function service(?UserRepository $repository = null, ?EntityManagerInterface $entityManager = null, ?UserPasswordHasherInterface $hasher = null, ?MailerInterface $mailer = null): PasswordResetService
    {
        $userMailer = new UserMailer($mailer ?? $this->createStub(MailerInterface::class), 'no-reply@example.com', 'TaskDiary');
        return new PasswordResetService(
            $repository ?? $this->createStub(UserRepository::class),
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $hasher ?? $this->createStub(UserPasswordHasherInterface::class),
            $userMailer,
            'https://taskdiary.test',
        );
    }
}
