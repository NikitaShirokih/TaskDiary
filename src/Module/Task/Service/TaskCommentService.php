<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Main\Service\AuthenticatedUserProvider;
use App\Module\Task\Dto\TaskCommentData;
use App\Module\Task\Entity\Task;
use App\Module\Task\Entity\TaskComment;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TaskCommentService
{
    public function __construct(
        private AuthenticatedUserProvider $authenticatedUserProvider,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function addComment(Task $task, TaskCommentData $data): void
    {
        $comment = TaskComment::create(
            task: $task,
            author: $this->authenticatedUserProvider->getUser(),
            content: trim($data->content),
        );

        $this->entityManager->persist($comment);
        $this->entityManager->flush();
    }
}
