<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Task\Enum\TaskRights;
use App\Module\Task\Enum\TaskStatus;
use App\Module\Main\Enum\UserRole;
use App\Module\Task\Exception\TaskNotFoundException;
use App\Module\Task\Service\TaskService;
use InvalidArgumentException;
use LogicException;
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

        $statusValue = trim((string) $request->request->get('status', ''));

        if ('' === $statusValue) {
            $this->addFlash('error', 'Не передан новый статус задачи.');

            return $this->redirectToRoute('task_list');
        }

        $status = TaskStatus::tryFrom($statusValue);

        if (!$status instanceof TaskStatus) {
            $this->addFlash('error', 'Некорректный статус задачи.');

            return $this->redirectToRoute('task_list');
        }

        try {
            $taskId = $task->getId();

            if (null === $taskId) {
                throw new LogicException('Задача должна быть сохранена перед изменением статуса.');
            }

            $this->taskService->updateStatus(
                id: $taskId,
                status: $status->value,
            );

            $this->addFlash('success', 'Статус задачи обновлён.');
        } catch (InvalidArgumentException|LogicException|TaskNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('task_list');
    }
}
