<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Task\Enum\TaskRights;
use App\Module\Main\Enum\UserRole;
use App\Module\Task\Service\TaskService;
use App\Module\Task\Service\TaskStatusRequestHandler;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::USER->value)]
#[Route('/task', name: 'task_')]
final class TaskStatusController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskStatusRequestHandler $taskStatusRequestHandler,
    ) {
    }

    #[Route('/{id<\d+>}/status', name: 'update_status', methods: ['POST'])]
    public function updateStatus(Request $request, int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::EDIT->value, $task);

        if (!$this->isCsrfTokenValid('task_status_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Недействительный CSRF-токен.');
        }

        try {
            $status = $this->taskStatusRequestHandler->handle($request);

            $this->taskService->updateStatus($task, $status);

            $this->addFlash('success', 'Статус задачи обновлён.');
        } catch (InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('task_list');
    }
}
