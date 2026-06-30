<?php

declare(strict_types=1);

namespace App\Module\Task\Repository;

use App\Module\Main\Entity\User;
use App\Module\Task\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function save(Category $category, bool $flush = false): void
    {
        $this->getEntityManager()->persist($category);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Category $category, bool $flush = false): void
    {
        $this->getEntityManager()->remove($category);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @return list<Category> */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('category')
            ->andWhere('category.user = :user')
            ->setParameter('user', $user)
            ->orderBy('category.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndId(User $user, int $id): ?Category
    {
        return $this->createQueryBuilder('category')
            ->andWhere('category.user = :user')
            ->andWhere('category.id = :id')
            ->setParameter('user', $user)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByUserAndName(User $user, string $name): ?Category
    {
        return $this->createQueryBuilder('category')
            ->andWhere('category.user = :user')
            ->andWhere('LOWER(category.name) = LOWER(:name)')
            ->setParameter('user', $user)
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
