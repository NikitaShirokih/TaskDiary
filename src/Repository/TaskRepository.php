<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findTasksByView(
        ?int    $categoryId = null,
        ?string $priority   = null,
        string  $view       = 'active',
        ?User   $user       = null,
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.children', 'sub')
            ->addSelect('sub')
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

        if ($view === 'completed') {
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

    public function findByStatus(string $status): array
    {
        $taskStatus = TaskStatus::tryFrom($status)
            ?? throw new \InvalidArgumentException(
                sprintf('Некорректный статус: "%s"', $status)
            );

        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('status', $taskStatus->value)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByPriority(string $priority): array
    {
        $taskPriority = TaskPriority::tryFrom($priority)
            ?? throw new \InvalidArgumentException(
                sprintf('Некорректный приоритет: "%s"', $priority)
            );

        return $this->createQueryBuilder('t')
            ->andWhere('t.priority = :priority')
            ->setParameter('priority', $taskPriority->value)
            ->orderBy('t.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTodayTasks(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.startTime >= :today')
            ->andWhere('t.startTime < :tomorrow')
            ->setParameter('today',    new \DateTimeImmutable('today'))
            ->setParameter('tomorrow', new \DateTimeImmutable('tomorrow'))
            ->orderBy('t.startTime', 'ASC');

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOverdueTasks(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.endTime < :now')
            ->andWhere('t.status != :completed')
            ->setParameter('now',       new \DateTimeImmutable())
            ->setParameter('completed', TaskStatus::Completed->value)
            ->orderBy('t.endTime', 'ASC');

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    public function countAll(?User $user = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countByStatus(TaskStatus $status, ?User $user = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.status = :status')
            ->setParameter('status', $status->value);

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countOverdue(?User $user = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.endTime < :now')
            ->andWhere('t.status != :completed')
            ->setParameter('now',       new \DateTimeImmutable())
            ->setParameter('completed', TaskStatus::Completed->value);

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countActive(?User $user = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('statuses', [
                TaskStatus::Waiting->value,
                TaskStatus::InProgress->value,
            ]);

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countDueToday(?User $user = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.endTime >= :startOfDay')
            ->andWhere('t.endTime < :endOfDay')
            ->andWhere('t.status NOT IN (:done)')
            ->setParameter('startOfDay', new \DateTimeImmutable('today'))
            ->setParameter('endOfDay',   new \DateTimeImmutable('tomorrow'))
            ->setParameter('done', [
                TaskStatus::Completed->value,
                TaskStatus::InProgress->value,
            ]);

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countDueThisWeek(?User $user = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.endTime >= :now')
            ->andWhere('t.endTime < :inWeek')
            ->andWhere('t.status NOT IN (:done)')
            ->setParameter('now',    new \DateTimeImmutable())
            ->setParameter('inWeek', new \DateTimeImmutable('+7 days'))
            ->setParameter('done', [TaskStatus::Completed->value]);

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getStatistics(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('
                COUNT(t.id) as total,
                SUM(CASE WHEN t.status = :waiting     THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN t.status = :in_progress THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN t.status = :completed   THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN t.endTime < :now AND t.status != :completed THEN 1 ELSE 0 END) as overdue
            ')
            ->setParameter('waiting',     TaskStatus::Waiting->value)
            ->setParameter('in_progress', TaskStatus::InProgress->value)
            ->setParameter('completed',   TaskStatus::Completed->value)
            ->setParameter('now',         new \DateTimeImmutable());

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getSingleResult();
    }

    public function getCategoryChartData(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('c.name, c.color, COUNT(t.id) as count')
            ->leftJoin('t.category', 'c')
            ->andWhere('t.parent IS NULL')
            ->groupBy('c.id')
            ->orderBy('count', 'DESC');

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function getCalendarEvents(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->addSelect('c')
            ->andWhere('t.startTime IS NOT NULL')
            ->andWhere('t.parent IS NULL')
            ->andWhere('t.status != :completed')
            ->setParameter('completed', TaskStatus::Completed->value);

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        $tasks = $qb->getQuery()->getResult();

        return array_map(
            static fn(Task $task): array => [
                'title' => $task->getTitle(),
                'start' => $task->getStartTime()->format('Y-m-d\TH:i:s'),
                'color' => $task->getCategory()?->getColor()
                    ?? self::resolveStatusColor($task->getStatus()),
            ],
            $tasks,
        );
    }

    public function save(Task $task, bool $flush = false): void
    {
        $this->getEntityManager()->persist($task);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Task $task, bool $flush = false): void
    {
        $this->getEntityManager()->remove($task);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    private static function resolveStatusColor(TaskStatus $status): string
    {
        return match ($status) {
            TaskStatus::Completed  => '#198754',
            TaskStatus::InProgress => '#ffc107',
            TaskStatus::Waiting    => '#6c757d',
        };
    }

    public function getProductivityByDays(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUBSTRING(t.updatedAt, 1, 10) as date', 'COUNT(t.id) as count')
            ->andWhere('t.parent IS NULL')
            ->andWhere('t.status = :completed')
            ->andWhere('t.updatedAt >= :from')
            ->setParameter('from', new \DateTimeImmutable("-30 days"))
            ->setParameter('completed', TaskStatus::Completed->value)
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        if ($user !== null) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function getAvgCompletionByDays(?User $user = null): float
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT AVG(TIMESTAMPDIFF(SECOND, t.created_at, t.updated_at))
            FROM tasks t
            WHERE t.parent_id IS NULL
              AND t.status = :completed
              AND t.updated_at >= :from'
            . ($user !== null ? ' AND t.user_id = :user' : '');

        $params = [
            'completed' => TaskStatus::Completed->value,
            'from'      => (new \DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s'),
        ];
        if ($user !== null) {
            $params['user'] = $user->getId();
        }

        $result = $conn->fetchOne($sql, $params);
        return round((float) $result / 86400, 1);
    }


    public function getBurndownData(?User $user = null): array
    {
        $from = new \DateTimeImmutable('-30 days');

        $createdQb = $this->createQueryBuilder('t')
            ->select('SUBSTRING(t.createdAt, 1, 10) as date', 'COUNT(t.id) as count')
            ->andWhere('t.parent IS NULL')
            ->andWhere('t.createdAt >= :from')
            ->setParameter('from', $from)
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        $completedQb = $this->createQueryBuilder('t')
            ->select('SUBSTRING(t.updatedAt, 1, 10) as date', 'COUNT(t.id) as count')
            ->andWhere('t.parent IS NULL')
            ->andWhere('t.status = :completed')
            ->andWhere('t.updatedAt >= :from')
            ->setParameter('from', $from)
            ->setParameter('completed', TaskStatus::Completed->value)
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        if ($user !== null) {
            $createdQb->andWhere('t.user = :user')->setParameter('user', $user);
            $completedQb->andWhere('t.user = :user')->setParameter('user', $user);
        }

        return [
            'created'   => $createdQb->getQuery()->getArrayResult(),
            'completed' => $completedQb->getQuery()->getArrayResult(),
        ];
    }
}
