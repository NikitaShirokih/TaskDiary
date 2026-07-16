<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module\Main\Auth;

use App\Module\Main\Entity\User;
use App\Module\Main\Security\SecureTokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthWebFlowTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();

        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        $this->entityManager->clear();

        parent::tearDown();
    }

    public function testRegistrationCreatesUnverifiedUser(): void
    {
        $email = $this->uniqueEmail();
        $plainPassword = 'Registration-password-1';

        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Зарегистрироваться', [
            'registration_form[email]' => $email,
            'registration_form[plainPassword][password]' => $plainPassword,
            'registration_form[plainPassword][confirm_password]' => $plainPassword,
        ]);

        self::assertResponseRedirects('/login');
        $this->flushAndClear();

        $user = $this->findUserByEmail($email);
        self::assertSame($email, $user->getEmail());
        self::assertFalse($user->isVerified());
        self::assertNotNull($user->getEmailVerificationTokenHash());
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $user->getEmailVerificationTokenHash());
        self::assertNotSame($plainPassword, $user->getPassword());
        self::assertTrue($this->passwordHasher->isPasswordValid($user, $plainPassword));
    }

    public function testUnverifiedUserCannotLogin(): void
    {
        $email = $this->uniqueEmail();
        $password = 'Unverified-password-1';
        $this->createUser($email, $password);

        $this->login($email, $password);

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorExists('.alert-danger');

        $this->client->request('GET', '/task/');
        self::assertResponseRedirects('http://localhost/login');
    }

    public function testEmailVerificationConfirmsUser(): void
    {
        $user = $this->createUser($this->uniqueEmail(), 'Verification-password-1');
        $tokenGenerator = self::getContainer()->get(SecureTokenGenerator::class);
        $rawToken = $tokenGenerator->generateRawToken();
        $tokenHash = $tokenGenerator->hashToken($rawToken);
        $user->requestEmailVerification($tokenHash, new \DateTimeImmutable('+1 hour'));
        $this->entityManager->flush();

        self::assertNotSame($rawToken, $user->getEmailVerificationTokenHash());

        $this->client->request('GET', '/email/verify/'.$rawToken);

        self::assertResponseRedirects('/login');
        $email = (string) $user->getEmail();
        $this->flushAndClear();
        $verifiedUser = $this->findUserByEmail($email);
        self::assertTrue($verifiedUser->isVerified());
        self::assertNull($verifiedUser->getEmailVerificationTokenHash());
        self::assertNull($verifiedUser->getEmailVerificationTokenExpiresAt());
    }

    public function testVerifiedUserCanLogin(): void
    {
        $email = $this->uniqueEmail();
        $password = 'Verified-password-1';
        $this->createUser($email, $password, true);

        $this->login($email, $password);

        self::assertResponseRedirects('/dashboard');
        $this->client->request('GET', '/task/');
        self::assertResponseIsSuccessful();
    }

    public function testForgotPasswordCreatesResetTokenHash(): void
    {
        $email = $this->uniqueEmail();
        $user = $this->createUser($email, 'Forgot-password-1', true);
        $passwordHash = $user->getPassword();

        $this->client->request('GET', '/forgot-password');
        self::assertResponseIsSuccessful();
        $this->client->submitForm('Отправить ссылку', [
            'forgot_password_form[email]' => $email,
        ]);

        self::assertResponseRedirects('/login');
        $this->flushAndClear();
        $updatedUser = $this->findUserByEmail($email);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $updatedUser->getPasswordResetTokenHash());
        self::assertNotNull($updatedUser->getPasswordResetTokenExpiresAt());
        self::assertSame($passwordHash, $updatedUser->getPassword());
    }

    public function testResetPasswordChangesPasswordAndClearsToken(): void
    {
        $email = $this->uniqueEmail();
        $oldPassword = 'Old-password-1';
        $newPassword = 'New-password-2';
        $user = $this->createUser($email, $oldPassword, true);
        $oldPasswordHash = $user->getPassword();
        $tokenGenerator = self::getContainer()->get(SecureTokenGenerator::class);
        $rawToken = $tokenGenerator->generateRawToken();
        $user->requestPasswordReset($tokenGenerator->hashToken($rawToken), new \DateTimeImmutable('+1 hour'));
        $this->entityManager->flush();

        $this->client->request('GET', '/reset-password/'.$rawToken);
        self::assertResponseIsSuccessful();
        $this->client->submitForm('Сохранить пароль', [
            'reset_password_form[plainPassword][first]' => $newPassword,
            'reset_password_form[plainPassword][second]' => $newPassword,
        ]);

        self::assertResponseRedirects('/login');
        $this->flushAndClear();
        $updatedUser = $this->findUserByEmail($email);
        self::assertNotSame($oldPasswordHash, $updatedUser->getPassword());
        self::assertFalse($this->passwordHasher->isPasswordValid($updatedUser, $oldPassword));
        self::assertTrue($this->passwordHasher->isPasswordValid($updatedUser, $newPassword));
        self::assertNull($updatedUser->getPasswordResetTokenHash());
        self::assertNull($updatedUser->getPasswordResetTokenExpiresAt());

        $this->login($email, $oldPassword);
        self::assertResponseRedirects('/login');
        $this->client->followRedirect();

        $this->login($email, $newPassword);
        self::assertResponseRedirects('/dashboard');
        $this->client->request('GET', '/task/');
        self::assertResponseIsSuccessful();
    }

    public function testInvalidResetTokenDoesNotChangePassword(): void
    {
        $email = $this->uniqueEmail();
        $user = $this->createUser($email, 'Untouched-password-1', true);
        $passwordHash = $user->getPassword();
        $tokenGenerator = self::getContainer()->get(SecureTokenGenerator::class);
        $user->requestPasswordReset(
            $tokenGenerator->hashToken($tokenGenerator->generateRawToken()),
            new \DateTimeImmutable('+1 hour'),
        );
        $resetTokenHash = $user->getPasswordResetTokenHash();
        $this->entityManager->flush();

        $this->client->request('GET', '/reset-password/invalid-token');

        self::assertResponseRedirects('/forgot-password');
        $this->flushAndClear();
        $unchangedUser = $this->findUserByEmail($email);
        self::assertSame($passwordHash, $unchangedUser->getPassword());
        self::assertSame($resetTokenHash, $unchangedUser->getPasswordResetTokenHash());
        self::assertNotNull($unchangedUser->getPasswordResetTokenExpiresAt());
    }

    private function uniqueEmail(): string
    {
        return sprintf('auth-test-%s@example.com', bin2hex(random_bytes(4)));
    }

    private function findUserByEmail(string $email): User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    private function createUser(string $email, string $plainPassword, bool $verified = false): User
    {
        $user = (new User())->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        if ($verified) {
            $user->verifyEmail(new \DateTimeImmutable());
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function flushAndClear(): void
    {
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function login(string $email, string $password): void
    {
        $this->client->request('GET', '/login');
        self::assertResponseIsSuccessful();
        $this->client->submitForm('Войти', [
            '_username' => $email,
            '_password' => $password,
        ]);
    }
}
