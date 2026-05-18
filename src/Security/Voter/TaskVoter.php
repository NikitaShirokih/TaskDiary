<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use App\Enum\TaskRights;

/**
 * @extends Voter<string, Task>
 */
final class TaskVoter extends Voter
{
    /** Cистемы авторизации на уровне объектов. */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return TaskRights::OWNER->value === $attribute && $subject instanceof Task;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        /** @var Task $subject */
        return $subject->getUser() === $user;
    }
}
