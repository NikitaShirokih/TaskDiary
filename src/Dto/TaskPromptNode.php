<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Task;
use App\Enum\TaskRelationType;

final readonly class TaskPromptNode
{
    public bool $hasChildren;
    /**
     * @param list<TaskPromptNode> $children
     */
    public function __construct(
        public Task $task,
        public TaskRelationType $relationType,
        public array $children = [],
        public int $hiddenRelationsCount = 0,
    ) {
        $this->hasChildren = !empty($children);
    }
}
