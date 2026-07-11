<?php

declare(strict_types=1);

namespace App\Module\Main\Repository;

use App\Module\Main\Entity\ApiToken;
use App\Module\Main\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiToken>
 */
final class ApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    public function findActiveByTokenHash(string $tokenHash): ?ApiToken
    {
        return $this->findOneBy([
            'tokenHash' => $tokenHash,
            'revokedAt' => null,
        ]);
    }

    /**
     * @return list<ApiToken>
     */
    public function findActiveForUser(User $user): array
    {
        return $this->findBy(
            [
                'user' => $user,
                'revokedAt' => null,
            ],
            ['createdAt' => 'DESC'],
        );
    }

    public function findOneActiveByIdAndUser(int $id, User $user): ?ApiToken
    {
        return $this->findOneBy([
            'id' => $id,
            'user' => $user,
            'revokedAt' => null,
        ]);
    }
}
