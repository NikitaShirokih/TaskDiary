<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

use App\Module\Main\Dto\RegistrationData;
use App\Module\Main\Entity\User;
use App\Module\Main\Exception\UserAlreadyExistsException;
use App\Module\Main\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly EmailVerificationService $emailVerificationService,
        private readonly UserMailer $userMailer,
        private readonly string $appBaseUrl,
    ) {
    }

    public function register(RegistrationData $data): User
    {
        $email = mb_strtolower(trim($data->email));

        $current = $this->userRepository->findOneBy(['email' => $email]);

        if ($current !== null) {
            throw UserAlreadyExistsException::byEmail($email);
        }

        $user = new User();
        $user->setEmail($email);

        $hashedPassword = $this->hasher->hashPassword($user, $data->plainPassword);
        $user->setPassword($hashedPassword);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw UserAlreadyExistsException::byEmail($email);
        }

        $token = $this->emailVerificationService->requestVerification($user);

        $verificationUrl = sprintf(
            '%s/email/verify/%s',
            rtrim($this->appBaseUrl, '/'),
            $token,
        );

        $this->userMailer->sendEmailVerification($user, $verificationUrl);

        return $user;
    }
}
