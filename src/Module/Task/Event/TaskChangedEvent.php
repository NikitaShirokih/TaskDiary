<?php

declare(strict_types=1);

namespace App\Module\Task\Event;

final readonly class TaskChangedEvent
{
    public function __construct(
        public int $userId,
    ) {
    }
}