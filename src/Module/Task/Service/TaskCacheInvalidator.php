<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use Symfony\Contracts\Cache\CacheInterface;

final readonly class TaskCacheInvalidator
{
    public function __construct(
        private CacheInterface $dashboardCache,
    ) {
    }

    public function invalidateDashboardForUserId(int $userId): void
    {
        $this->dashboardCache->delete(
            sprintf('dashboard_stats_user_%d', $userId)
        );
    }
}
