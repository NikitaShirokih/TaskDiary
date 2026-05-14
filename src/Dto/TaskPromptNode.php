<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\TaskRelationType;
use DateTimeInterface;

final readonly class TaskPromptNode
{
    /**
     * @param list<TaskPromptNode> $children
     */
    public function __construct(
        public ?int $id,
        public string $title,
        public ?string $description,
        public string $status,
        public string $priority,
        public ?string $type,
        public ?DateTimeInterface $startTime,
        public ?DateTimeInterface $endTime,
        public TaskRelationType $relationType,
        public array $children = [],
        public int $hiddenRelationsCount = 0,
    ) {
    }

    public function hasChildren(): bool
    {
        return [] !== $this->children;
    }
}
