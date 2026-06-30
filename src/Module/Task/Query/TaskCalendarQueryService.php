<?php

declare(strict_types=1);

namespace App\Module\Task\Query;

use App\Entity\Task;
use App\Entity\User;
use App\Module\Task\Enum\TaskStatus;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TaskCalendarQueryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCalendarEvents(?User $user = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t', 'c')
            ->from(Task::class, 't')
            ->leftJoin('t.category', 'c')
            ->andWhere('t.startTime IS NOT NULL')
            ->andWhere('t.parent IS NULL')
            ->andWhere('t.status != :completed')
            ->setParameter('completed', TaskStatus::Completed->value);

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        $tasks = $qb->getQuery()->getResult();

        return array_map(
            static fn (Task $task): array => [
                'title' => $task->getTitle(),
                'start' => $task->getStartTime()->format('Y-m-d\TH:i:s'),
                'color' => $task->getCategory()?->getColor()
                    ?? self::resolveStatusColor($task->getStatus()),
            ],
            $tasks,
        );
    }

    private static function resolveStatusColor(TaskStatus $status): string
    {
        return match ($status) {
            TaskStatus::Completed => '#198754',
            TaskStatus::InProgress => '#ffc107',
            TaskStatus::Waiting => '#6c757d',
        };
    }
}
