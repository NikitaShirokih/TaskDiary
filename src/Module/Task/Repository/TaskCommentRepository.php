<?php

declare(strict_types=1);

namespace App\Module\Task\Repository;

use App\Module\Task\Entity\TaskComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaskComment>
 */
final class TaskCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskComment::class);
    }
}
