<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(TaskRepository $taskRepository, CategoryRepository $categoryRepository): Response
    {
        $user = $this->getUser();

        $latestTasks = $taskRepository->findBy(['user' => $user], ['id' => 'DESC'], 5);
        $chartData   = $taskRepository->getCategoryChartData($user);

        $stats = [
            'activeTasks'      => $taskRepository->countActive($user),
            'dueTodayTasks'    => $taskRepository->countDueToday($user),
            'dueThisWeekTasks' => $taskRepository->countDueThisWeek($user),
        ];

        $categoryChart = [
            'labels' => array_column($chartData, 'name'),
            'data'   => array_map('intval', array_column($chartData, 'count')),
            'colors' => array_column($chartData, 'color'),
        ];

        $calendarEvents = $taskRepository->getCalendarEvents($user);

        foreach ($calendarEvents as &$event) {
            $event['url'] = $this->generateUrl('task_list');
        }
        unset($event);

        return $this->render('dashboard/index.html.twig', [
            'stats'          => $stats,
            'latestTasks'    => $latestTasks,
            'categoryChart'  => $categoryChart,
            'calendarEvents' => $calendarEvents,
        ]);
    }
}
