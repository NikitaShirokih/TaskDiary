<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class TaskPromptData
{
    public function __construct(
        public TaskPromptNode $parent,
        public string $instruction,
    ) {
    }
}
