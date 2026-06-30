<?php

declare(strict_types=1);

namespace App\Module\Task\Security\Voter;

use App\Entity\Task;
use App\Entity\User;
use App\Module\Task\Enum\TaskRights;
use App\Module\User\Enum\UserRole;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Task>
 */
final class TaskVoter extends Voter
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    /** Cистемы авторизации на уровне объектов. */
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Task) {
            return false;
        }
        return in_array($attribute, [
            TaskRights::VIEW->value,
            TaskRights::EDIT->value,
            TaskRights::DELETE->value,
        ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        /** @var Task $task */
        $task = $subject;

        if ($this->security->isGranted(UserRole::ADMIN->value)) {
            return true;
        }

        return $task->getUser()->getId() === $user->getId();
    }
}
