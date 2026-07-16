<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Main\Service;

use App\Module\Main\Dto\RegistrationData;
use App\Module\Main\Entity\User;
use App\Module\Main\Repository\UserRepository;
use App\Module\Main\Security\SecureTokenGenerator;
use App\Module\Main\Service\EmailVerificationService;
use App\Module\Main\Service\RegistrationService;
use App\Module\Main\Service\UserMailer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationServiceTest extends TestCase
{
    public function testRegistrationEmailsRawTokenAndStoresOnlyHash(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'user@example.com'])
            ->willReturn(null);

        $registeredUser = null;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('persist')
            ->willReturnCallback(static function (User $user) use (&$registeredUser): void {
                $registeredUser = $user;
            });
        $entityManager->expects($this->exactly(2))->method('flush');

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->once())->method('hashPassword')->willReturn('password-hash');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send')->with($this->callback(
            static function (Email $email) use (&$registeredUser): bool {
                preg_match('#https://taskdiary\.test/email/verify/([a-f0-9]{64})#', $email->getHtmlBody() ?? '', $matches);

                return $registeredUser instanceof User
                    && isset($matches[1])
                    && $matches[1] !== $registeredUser->getEmailVerificationTokenHash()
                    && hash('sha256', $matches[1]) === $registeredUser->getEmailVerificationTokenHash();
            },
        ));

        $secureTokenGenerator = new SecureTokenGenerator();
        $emailVerificationService = new EmailVerificationService(
            $repository,
            $entityManager,
            $secureTokenGenerator,
        );
        $userMailer = new UserMailer($mailer, 'no-reply@example.com', 'TaskDiary');
        $service = new RegistrationService(
            $repository,
            $entityManager,
            $hasher,
            $emailVerificationService,
            $userMailer,
            'https://taskdiary.test',
        );
        $data = new RegistrationData();
        $data->email = ' USER@example.com ';
        $data->plainPassword = 'password';

        $user = $service->register($data);

        self::assertSame($registeredUser, $user);
        self::assertSame('password-hash', $user->getPassword());
        self::assertNotNull($user->getEmailVerificationTokenHash());
    }
}
