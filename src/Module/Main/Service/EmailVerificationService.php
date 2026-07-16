<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

use App\Module\Main\Entity\User;
use App\Module\Main\Repository\UserRepository;
use App\Module\Main\Security\SecureTokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

final readonly class EmailVerificationService
{
    private const TOKEN_TTL_HOURS = 24;

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private SecureTokenGenerator $secureTokenGenerator,
    ) {
    }

    public function requestVerification(User $user): string
    {
        $rawToken = $this->secureTokenGenerator->generateRawToken();
        $tokenHash = $this->secureTokenGenerator->hashToken($rawToken);
        $expiresAt = new \DateTimeImmutable(sprintf('+%d hours', self::TOKEN_TTL_HOURS));

        $user->requestEmailVerification($tokenHash, $expiresAt);

        $this->entityManager->flush();

        return $rawToken;
    }

    public function verify(string $rawToken): User
    {
        $rawToken = trim($rawToken);

        if ($rawToken === '') {
            throw new RuntimeException('Некорректная ссылка подтверждения email.');
        }

        $user = $this->userRepository->findOneByEmailVerificationTokenHash(
            $this->secureTokenGenerator->hashToken($rawToken),
        );

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
