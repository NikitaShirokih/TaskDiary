<?php

namespace App\Tests\E2E;

use App\Module\Main\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginTest extends PantherTestCase
{
    public function testLoginSuccess(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer()->get('test.service_container');

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $existing = $em->getRepository(User::class)->findOneBy(['email' => 'test@test.com']);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }

        $user = new User();
        $user->setEmail('test@test.com');
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        self::ensureKernelShutdown();

        $client = static::createPantherClient([
            'env' => [
                'DATABASE_URL' => 'postgresql://user:password@127.0.0.1:5432/taskdiary?serverVersion=15&charset=utf8',
            ],
        ]);

        $client->request('GET', '/login');

        $client->submitForm('Войти', [
            '_username' => 'test@test.com',
            '_password' => 'password',
        ]);

        $this->assertStringContainsString('/dashboard', $client->getCurrentURL());
    }
}
