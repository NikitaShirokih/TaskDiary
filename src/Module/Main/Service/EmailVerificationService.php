<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

use App\Module\Main\Entity\User;
use App\Module\Main\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

final readonly class EmailVerificationService
{
    private const TOKEN_TTL_HOURS = 24;

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function requestVerification(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable(sprintf('+%d hours', self::TOKEN_TTL_HOURS));

        $user->requestEmailVerification($token, $expiresAt);

        $this->entityManager->flush();

        return $token;
    }

    public function verify(string $token): User
    {
        $token = trim($token);

        if ($token === '') {
            throw new RuntimeException('Некорректная ссылка подтверждения email.');
        }

        $user = $this->userRepository->findOneByEmailVerificationToken($token);

        if (!$user instanceof User) {
            throw new RuntimeException('Ссылка подтверждения email недействительна.');
        }

        $now = new \DateTimeImmutable();

        if ($user->isEmailVerificationTokenExpired($now)) {
            throw new RuntimeException('Срок действия ссылки подтверждения email истёк.');
        }

        $user->verifyEmail($now);

        $this->entityManager->flush();

        return $user;
    }
}
