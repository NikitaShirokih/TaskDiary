<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Task\Entity\Task;
use App\Module\Main\Entity\User;
use App\Module\Task\Enum\TaskPriority;
use App\Module\Task\Enum\TaskStatus;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TaskListQueryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<int, Task>
     */
    public function findTasksByView(
        ?int $categoryId = null,
        ?string $priority = null,
        string $view = 'active',
        ?User $user = null,
    ): array {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t', 'sub')
            ->from(Task::class, 't')
            ->leftJoin('t.children', 'sub')
            ->andWhere('t.parent IS NULL')
            ->orderBy('t.createdAt', 'DESC');

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        if ($categoryId !== null) {
            $qb->leftJoin('t.category', 'c')
                ->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ($priority !== null && TaskPriority::tryFrom($priority) !== null) {
            $qb->andWhere('t.priority = :priority')
                ->setParameter('priority', TaskPriority::from($priority)->value);
        }

        if ('completed' === $view) {
            $qb->andWhere('t.status = :completed')
                ->setParameter('completed', TaskStatus::Completed->value);
        } else {
            $qb->andWhere('t.status IN (:statuses)')
                ->setParameter('statuses', [
                    TaskStatus::Waiting->value,
                    TaskStatus::InProgress->value,
                ]);
        }

        return $qb->getQuery()->getResult();
    }
}
