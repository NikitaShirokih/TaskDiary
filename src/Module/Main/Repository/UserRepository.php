<?php

namespace App\Module\Main\Repository;

use App\Module\Main\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmailVerificationTokenHash(string $tokenHash): ?User
    {
        return $this->findOneBy([
            'emailVerificationToken' => $tokenHash,
        ]);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy([
            'email' => mb_strtolower(trim($email)),
        ]);
    }

    public function findOneByPasswordResetTokenHash(string $tokenHash): ?User
    {
        return $this->findOneBy([
            'passwordResetTokenHash' => $tokenHash,
        ]);
    }
}
