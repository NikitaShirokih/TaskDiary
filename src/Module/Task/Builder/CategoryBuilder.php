<?php

declare(strict_types=1);

namespace App\Module\Task\Builder;

use App\Module\Task\Entity\Category;

final class CategoryBuilder
{
    /**
     * @return array{id: int|null, name: string|null, color: string|null}
     */
    public function build(Category $category): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'color' => $category->getColor(),
        ];
    }
}
