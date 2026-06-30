<?php

declare(strict_types=1);

namespace App\Module\Task\Dto;

final readonly class TaskPromptData
{
    public function __construct(
        public TaskPromptNode $parent,
        public string $instruction,
    ) {
    }
}
