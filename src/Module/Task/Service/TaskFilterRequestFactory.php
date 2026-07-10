<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Task\Dto\TaskFilter;
use Symfony\Component\HttpFoundation\Request;

final readonly class TaskFilterRequestFactory
{
    public function createFromRequest(Request $request): TaskFilter
    {
        $categoryValue = $request->query->get('category');

        $priorityValue = $request->query->get('priority');

        return new TaskFilter(
            categoryId: $categoryValue !== null && '' !== $categoryValue ? (int) $categoryValue : null,
            priority: is_string($priorityValue) ? $priorityValue : null,
            view: $request->query->getString('view', 'active'),
        );
    }
}
