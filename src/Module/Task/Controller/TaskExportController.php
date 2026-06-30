<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Enum\TaskRights;
use App\Enum\UserRole;
use App\Service\TaskExportService;
use App\Service\TaskService;
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
    ) {
    }

    #[Route('/{id<\d+>}/export', name: 'export_json', methods: ['GET'])]
    public function exportJson(int $id, TaskExportService $exportService): JsonResponse
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::VIEW->value, $task);

        $tasks = $this->taskService->getTaskWithDescendantsForExport($id);

        return new JsonResponse(
            $exportService->exportTasks($task, $tasks),
            Response::HTTP_OK,
            ['Content-Disposition' => 'attachment; filename="task_'.$id.'.json"']
        );
    }
}
