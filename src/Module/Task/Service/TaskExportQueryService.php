<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Task\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TaskExportQueryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<int, Task>
     */
    public function findTaskWithDescendantsForExport(int $id): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('t', 'p')
            ->from(Task::class, 't')
            ->leftJoin('t.parent', 'p')
            ->andWhere('t.id = :id OR p.id = :id')
            ->setParameter('id', $id)
            ->orderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
