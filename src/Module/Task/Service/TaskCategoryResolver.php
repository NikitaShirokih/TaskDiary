<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Main\Entity\User;
use App\Module\Task\Entity\Category;
use App\Module\Task\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

final readonly class TaskCategoryResolver
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function resolveForUser(User $user, string $categoryName): Category
    {
        $name = trim($categoryName);

        if ('' === $name) {
            throw new RuntimeException('Для основной задачи необходимо выбрать категорию.');
        }

        $category = $this->categoryRepository->findOneByUserAndName($user, $name);

        if ($category instanceof Category) {
            return $category;
        }

        $category = new Category($user);
        $category->setName($name);
        $category->setColor('#3498db');
        $category->setIcon(null);
        $category->setDescription(null);

        $this->entityManager->persist($category);

        return $category;
    }
}
