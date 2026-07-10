<?php

declare(strict_types=1);

namespace App\Module\Main\DataFixtures;

use App\Module\Main\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture
{
    public const USER_REFERENCE = 'main-user';
    public const ADMIN_REFERENCE = 'admin-user';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'qwerty'));
        $user->verifyEmail(new \DateTimeImmutable());

        $manager->persist($user);
        $this->addReference(self::USER_REFERENCE, $user);

        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'qwerty'));
        $user->verifyEmail(new \DateTimeImmutable());

        $manager->persist($user);
        $this->addReference(self::ADMIN_REFERENCE, $user);

        $manager->flush();
    }
}
