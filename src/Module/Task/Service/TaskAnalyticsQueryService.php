<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Task\Entity\Task;
use App\Module\Main\Entity\User;
use App\Module\Task\Enum\TaskStatus;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TaskAnalyticsQueryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatistics(?User $user = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('
                COUNT(t.id) as total,
                SUM(CASE WHEN t.status = :waiting     THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN t.status = :in_progress THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN t.status = :completed   THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN t.endTime < :now AND t.status != :completed THEN 1 ELSE 0 END) as overdue
            ')
            ->from(Task::class, 't')
            ->setParameter('waiting', TaskStatus::Waiting->value)
            ->setParameter('in_progress', TaskStatus::InProgress->value)
            ->setParameter('completed', TaskStatus::Completed->value)
            ->setParameter('now', new \DateTimeImmutable());

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCategoryChartData(?User $user = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('c.name, c.color, COUNT(t.id) as count')
            ->from(Task::class, 't')
            ->leftJoin('t.category', 'c')
            ->andWhere('t.parent IS NULL')
            ->groupBy('c.id')
            ->orderBy('count', 'DESC');

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getProductivityByDays(?User $user = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('SUBSTRING(t.updatedAt, 1, 10) as date', 'COUNT(t.id) as count')
            ->from(Task::class, 't')
            ->andWhere('t.parent IS NULL')
            ->andWhere('t.status = :completed')
            ->andWhere('t.updatedAt >= :from')
            ->setParameter('from', new \DateTimeImmutable('-30 days'))
            ->setParameter('completed', TaskStatus::Completed->value)
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function getAvgCompletionByDays(?User $user = null): float
    {
        $conn = $this->entityManager->getConnection();

        $sql = 'SELECT AVG(TIMESTAMPDIFF(SECOND, t.created_at, t.updated_at))
            FROM tasks t
            WHERE t.parent_id IS NULL
              AND t.status = :completed
              AND t.updated_at >= :from'
            .(null !== $user ? ' AND t.user_id = :user' : '');

        $params = [
            'completed' => TaskStatus::Completed->value,
            'from' => (new \DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s'),
        ];

        if (null !== $user) {
            $params['user'] = $user->getId();
        }

        $result = $conn->fetchOne($sql, $params);

        return round((float) $result / 86400, 1);
    }

    /**
     * @return array{created: array<int, array<string, mixed>>, completed: array<int, array<string, mixed>>}
     */
    public function getBurndownData(?User $user = null): array
    {
        $from = new \DateTimeImmutable('-30 days');

        $createdQb = $this->entityManager->createQueryBuilder()
            ->select('SUBSTRING(t.createdAt, 1, 10) as date', 'COUNT(t.id) as count')
            ->from(Task::class, 't')
            ->andWhere('t.parent IS NULL')
            ->andWhere('t.createdAt >= :from')
            ->setParameter('from', $from)
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        $completedQb = $this->entityManager->createQueryBuilder()
            ->select('SUBSTRING(t.updatedAt, 1, 10) as date', 'COUNT(t.id) as count')
            ->from(Task::class, 't')
            ->andWhere('t.parent IS NULL')
            ->andWhere('t.status = :completed')
            ->andWhere('t.updatedAt >= :from')
            ->setParameter('from', $from)
            ->setParameter('completed', TaskStatus::Completed->value)
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        if (null !== $user) {
            $createdQb->andWhere('t.user = :user')->setParameter('user', $user);
            $completedQb->andWhere('t.user = :user')->setParameter('user', $user);
        }

        return [
            'created' => $createdQb->getQuery()->getArrayResult(),
            'completed' => $completedQb->getQuery()->getArrayResult(),
        ];
    }
}
