<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

use App\Module\Main\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class AuthenticatedUserProvider
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function getUser(): User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('Пользователь не авторизован.');
        }

        return $user;
    }
}
