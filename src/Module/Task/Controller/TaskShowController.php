<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Task\Enum\TaskRights;
use App\Module\Main\Enum\UserRole;
use App\Module\Task\Service\TaskShowPageProvider;
use App\Module\Task\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::USER->value)]
#[Route('/task', name: 'task_')]
final class TaskShowController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskShowPageProvider $taskShowPageProvider,
    ) {
    }

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::VIEW->value, $task);

        return $this->render('task/show.html.twig', $this->taskShowPageProvider->getPageData($task));
    }
}
