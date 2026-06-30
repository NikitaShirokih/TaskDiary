<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Task\Entity\Task;
use App\Module\Main\Entity\User;
use App\Module\Task\Enum\TaskStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class DashboardStatsQueryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $dashboardCache,
    ) {
    }

    /**
     * @return array<int, Task>
     */
    public function getLatestTasks(User $user, int $limit = 5): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Task::class, 't')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{activeTasks: int, dueTodayTasks: int, dueThisWeekTasks: int}
     */
    public function getStats(User $user): array
    {
        $stats = $this->dashboardCache->get(
            $this->getDashboardCacheKey($user),
            function (ItemInterface $item) use ($user): array {
                $item->expiresAfter(600);

                return $this->buildStats($user);
            }
        );

        return $stats;
    }

    private function getDashboardCacheKey(User $user): string
    {
        $userId = $user->getId();

        if (null === $userId) {
            throw new \LogicException('Authenticated user must have an id.');
        }

        return sprintf('dashboard_stats_user_%d', $userId);
    }

    /**
     * @return array{activeTasks: int, dueTodayTasks: int, dueThisWeekTasks: int}
     */
    private function buildStats(User $user): array
    {
        return [
            'activeTasks' => $this->countActive($user),
            'dueTodayTasks' => $this->countDueToday($user),
            'dueThisWeekTasks' => $this->countDueThisWeek($user),
        ];
    }

    public function countActive(?User $user = null): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Task::class, 't')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('statuses', [
                TaskStatus::Waiting->value,
                TaskStatus::InProgress->value,
            ]);

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countDueToday(?User $user = null): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Task::class, 't')
            ->andWhere('t.endTime >= :startOfDay')
            ->andWhere('t.endTime < :endOfDay')
            ->andWhere('t.status NOT IN (:done)')
            ->setParameter('startOfDay', new \DateTimeImmutable('today'))
            ->setParameter('endOfDay', new \DateTimeImmutable('tomorrow'))
            ->setParameter('done', [
                TaskStatus::Completed->value,
                TaskStatus::InProgress->value,
            ]);

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countDueThisWeek(?User $user = null): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Task::class, 't')
            ->andWhere('t.endTime >= :now')
            ->andWhere('t.endTime < :inWeek')
            ->andWhere('t.status NOT IN (:done)')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('inWeek', new \DateTimeImmutable('+7 days'))
            ->setParameter('done', [TaskStatus::Completed->value]);

        if (null !== $user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
