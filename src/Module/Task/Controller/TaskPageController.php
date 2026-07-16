<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Main\Enum\UserRole;
use App\Module\Task\Enum\TaskRights;
use App\Module\Task\Service\TaskListPageProvider;
use App\Module\Task\Service\TaskService;
use App\Module\Task\Service\TaskShowPageProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::USER->value)]
#[Route('/task', name: 'task_')]
final class TaskPageController extends AbstractController
{
    public function __construct(
        private readonly TaskListPageProvider $taskListPageProvider,
        private readonly TaskService $taskService,
        private readonly TaskShowPageProvider $taskShowPageProvider,
    ) {
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        return $this->render('task/list.html.twig', $this->taskListPageProvider->getPageData($request));
    }

    #[Route('/ajax/list', name: 'ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request): Response
    {
        return $this->render('task/_tasks_table.html.twig', $this->taskListPageProvider->getAjaxData($request));
    }

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::VIEW->value, $task);

        return $this->render('task/show.html.twig', $this->taskShowPageProvider->getPageData($task));
    }
}
