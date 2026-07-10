<?php

declare(strict_types=1);

namespace App\Module\Task\Dto;

final readonly class TaskFormResult
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        public ?TaskData $taskData,
        public bool $isSubmitted,
        public bool $isValid,
        public array $errors,
    ) {
    }
}
