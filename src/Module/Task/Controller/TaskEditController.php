<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Task\Dto\TaskData;
use App\Module\Task\Enum\TaskRights;
use App\Module\Main\Enum\UserRole;
use App\Module\Task\Service\TaskFormHandler;
use App\Module\Task\Service\TaskService;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::USER->value)]
#[Route('/task', name: 'task_')]
final class TaskEditController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskFormHandler $taskFormHandler,
    ) {
    }

    #[Route('/{id<\d+>}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::EDIT->value, $task);

        if (!$request->isMethod('POST')) {
            return $this->render('task/edit.html.twig', [
                'task' => $task,
                'taskData' => null,
            ]);
        }

        if (!$this->isCsrfTokenValid('task_edit', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Недействительный CSRF-токен.');
        }

        $result = $this->taskFormHandler->handle($request);

        if ($result->isValid && $result->taskData instanceof TaskData) {
            try {
                $this->taskService->updateTask($id, $result->taskData);
                $this->addFlash('success', 'Задача успешно обновлена.');

                return $this->redirectToRoute('task_list');
            } catch (InvalidArgumentException|LogicException|RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        foreach ($result->errors as $error) {
            $this->addFlash('error', $error);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'taskData' => $result->taskData,
        ]);
    }
}
