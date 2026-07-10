<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Main\Service\AuthenticatedUserProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class DashboardPageProvider
{
    public function __construct(
        private AuthenticatedUserProvider $authenticatedUserProvider,
        private DashboardStatsQueryService $dashboardStatsQueryService,
        private TaskAnalyticsQueryService $taskAnalyticsQueryService,
        private TaskCalendarQueryService $taskCalendarQueryService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getPageData(): array
    {
        $user = $this->authenticatedUserProvider->getUser();
        $chartData = $this->taskAnalyticsQueryService->getCategoryChartData($user);
        $calendarEvents = $this->taskCalendarQueryService->getCalendarEvents($user);

        foreach ($calendarEvents as &$event) {
            $event['url'] = $this->urlGenerator->generate('task_list');
        }

        unset($event);

        return [
            'stats' => $this->dashboardStatsQueryService->getStats($user),
            'latestTasks' => $this->dashboardStatsQueryService->getLatestTasks($user),
            'categoryChart' => [
                'labels' => array_column($chartData, 'name'),
                'data' => array_map('intval', array_column($chartData, 'count')),
                'colors' => array_column($chartData, 'color'),
            ],
            'calendarEvents' => $calendarEvents,
        ];
    }
}
