<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

use App\Module\Main\Dto\CreatedApiTokenResult;
use App\Module\Main\Entity\ApiToken;
use App\Module\Main\Entity\User;
use App\Module\Main\Repository\ApiTokenRepository;
use App\Module\Main\Security\SecureTokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final readonly class ApiTokenService
{
    public function __construct(
        private ApiTokenRepository $apiTokenRepository,
        private EntityManagerInterface $entityManager,
        private SecureTokenGenerator $secureTokenGenerator,
    ) {
    }

    public function createToken(User $user, string $name): CreatedApiTokenResult
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Введите название токена.');
        }

        $plainToken = 'td_' . $this->secureTokenGenerator->generateRawToken();
        $tokenHash = $this->secureTokenGenerator->hashToken($plainToken);
        $apiToken = new ApiToken($user, $name, $tokenHash);

        $this->entityManager->persist($apiToken);
        $this->entityManager->flush();

        return new CreatedApiTokenResult($apiToken, $plainToken);
    }

    /**
     * @return list<ApiToken>
     */
    public function getActiveTokens(User $user): array
    {
        return $this->apiTokenRepository->findActiveForUser($user);
    }

    public function revokeToken(User $user, int $tokenId): void
    {
        $apiToken = $this->apiTokenRepository->findOneActiveByIdAndUser($tokenId, $user);

        if (!$apiToken instanceof ApiToken) {
            throw new InvalidArgumentException('API token не найден.');
        }

        $apiToken->revoke(new \DateTimeImmutable());
        $this->entityManager->flush();
    }
}
