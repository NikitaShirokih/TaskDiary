<?php

declare(strict_types=1);

namespace App\Module\Dashboard\Controller;

use App\Entity\User;
use App\Module\Task\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getAuthenticatedUser();

        $latestTasks = $this->taskRepository->findBy(
            ['user' => $user],
            ['id' => 'DESC'],
            5
        );

        $chartData = $this->taskRepository->getCategoryChartData($user);

        $stats = [
            'activeTasks' => $this->taskRepository->countActive($user),
            'dueTodayTasks' => $this->taskRepository->countDueToday($user),
            'dueThisWeekTasks' => $this->taskRepository->countDueThisWeek($user),
        ];

        $categoryChart = [
            'labels' => array_column($chartData, 'name'),
            'data' => array_map('intval', array_column($chartData, 'count')),
            'colors' => array_column($chartData, 'color'),
        ];

        $calendarEvents = $this->taskRepository->getCalendarEvents($user);

        foreach ($calendarEvents as &$event) {
            $event['url'] = $this->generateUrl('task_list');
        }

        unset($event);

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'latestTasks' => $latestTasks,
            'categoryChart' => $categoryChart,
            'calendarEvents' => $calendarEvents,
        ]);
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Пользователь не авторизован.');
        }

        return $user;
    }
}
