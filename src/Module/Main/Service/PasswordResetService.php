<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

use App\Module\Main\Dto\ResetPasswordData;
use App\Module\Main\Entity\User;
use App\Module\Main\Exception\PasswordResetException;
use App\Module\Main\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class PasswordResetService
{
    private const TOKEN_TTL_HOURS = 1;

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $hasher,
        private UserMailer $userMailer,
        private string $appBaseUrl,
    ) {
    }

    public function requestReset(string $email): void
    {
        $email = mb_strtolower(trim($email));
        $user = $this->userRepository->findOneByEmail($email);

        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            return;
        }

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = new \DateTimeImmutable(sprintf('+%d hour', self::TOKEN_TTL_HOURS));

        $user->requestPasswordReset($tokenHash, $expiresAt);

        $this->entityManager->flush();

        $resetUrl = sprintf(
            '%s/reset-password/%s',
            rtrim($this->appBaseUrl, '/'),
            $rawToken,
        );

        $this->userMailer->sendPasswordReset($user, $resetUrl);
    }

    public function resetPassword(string $rawToken, ResetPasswordData $data): User
    {
        $user = $this->getUserByRawToken($rawToken);

        $user->setPassword($this->hasher->hashPassword($user, $data->plainPassword));
        $user->clearPasswordResetToken();

        $this->entityManager->flush();

        return $user;
    }

    public function assertTokenCanBeUsed(string $rawToken): void
    {
        $this->getUserByRawToken($rawToken);
    }

    private function getUserByRawToken(string $rawToken): User
    {
        $rawToken = trim($rawToken);

        if ($rawToken === '') {
            throw PasswordResetException::invalidToken();
        }

        $user = $this->userRepository->findOneByPasswordResetTokenHash(hash('sha256', $rawToken));

        if (!$user instanceof User) {
            throw PasswordResetException::invalidToken();
        }

        $now = new \DateTimeImmutable();

        if ($user->isPasswordResetTokenExpired($now)) {
            throw PasswordResetException::expiredToken();
        }

        return $user;
    }
}
