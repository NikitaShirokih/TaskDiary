<?php

declare(strict_types=1);

namespace App\Module\Task\Dto;

final readonly class TaskFilter
{
    public function __construct(
        public ?int $categoryId,
        public ?string $priority,
        public string $view,
    ) {
    }
}
