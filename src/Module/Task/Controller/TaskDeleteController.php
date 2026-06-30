<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Task\Enum\TaskRights;
use App\Module\Main\Enum\UserRole;
use App\Module\Task\Exception\TaskNotFoundException;
use App\Module\Task\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::USER->value)]
#[Route('/task', name: 'task_')]
final class TaskDeleteController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {
    }

    #[Route('/{id<\d+>}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::DELETE->value, $task);

        if (!$this->isCsrfTokenValid('task-delete-'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Недействительный CSRF-токен.');
        }

        try {
            $this->taskService->deleteTask($id);
            $this->addFlash('success', 'Задача успешно удалена.');
        } catch (TaskNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('task_list');
    }
}
