<?php

declare(strict_types=1);

namespace App\Module\Task\EventListener;

use App\Module\Task\Event\TaskChangedEvent;
use App\Module\Task\Service\TaskCacheInvalidator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: TaskChangedEvent::class)]
final readonly class TaskCacheInvalidationListener
{
    public function __construct(
        private TaskCacheInvalidator $taskCacheInvalidator,
    ) {
    }

    public function __invoke(TaskChangedEvent $event): void
    {
        $this->taskCacheInvalidator->invalidateDashboardForUserId($event->userId);
    }
}
