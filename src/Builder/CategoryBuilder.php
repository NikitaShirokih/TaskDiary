<?php

declare(strict_types=1);

namespace App\Builder;

use App\Entity\Category;

final class CategoryBuilder
{
    /**
     * @return array<string, mixed>|null
     */
    public function build(?Category $category): ?array
    {
        if ($category === null) {
            return null;
        }

        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'color' => $category->getColor(),
        ];
    }
}
