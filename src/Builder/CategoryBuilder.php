<?php

declare(strict_types=1);

namespace App\Builder;

use App\Entity\Category;

final class CategoryBuilder
{
    /**
     * @return array<string, mixed>|
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
