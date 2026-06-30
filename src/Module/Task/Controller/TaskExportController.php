<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Task\Enum\TaskRights;
use App\Module\User\Enum\UserRole;
use App\Module\Task\Query\TaskExportQueryService;
use App\Module\Task\Service\TaskExportService;
use App\Module\Task\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::USER->value)]
#[Route('/task', name: 'task_')]
final class TaskExportController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskExportQueryService $taskExportQueryService,
    ) {
    }

    #[Route('/{id<\d+>}/export', name: 'export_json', methods: ['GET'])]
    public function exportJson(int $id, TaskExportService $exportService): JsonResponse
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::VIEW->value, $task);

        $tasks = $this->taskExportQueryService->findTaskWithDescendantsForExport($id);

        return new JsonResponse(
            $exportService->exportTasks($task, $tasks),
            Response::HTTP_OK,
            ['Content-Disposition' => 'attachment; filename="task_'.$id.'.json"']
        );
    }
}
