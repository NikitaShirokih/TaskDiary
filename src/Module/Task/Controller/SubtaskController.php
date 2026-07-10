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
final class SubtaskController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskFormHandler $taskFormHandler,
    ) {
    }

    #[Route('/{id<\d+>}/subtask/new', name: 'subtask_new', methods: ['GET'])]
    public function newSubtask(int $id): Response
    {
        $parent = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::EDIT->value, $parent);

        return $this->render('task/create.html.twig', [
            'taskData' => null,
            'parent' => $parent,
        ]);
    }

    #[Route('/{id<\d+>}/subtask', name: 'subtask_create', methods: ['POST'])]
    public function createSubtask(Request $request, int $id): Response
    {
        $parent = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::EDIT->value, $parent);

        if (!$this->isCsrfTokenValid('subtask_create', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Недействительный CSRF-токен.');
        }

        $result = $this->taskFormHandler->handle($request);

        if ($result->isValid && $result->taskData instanceof TaskData) {
            try {
                $this->taskService->addSubtask($id, $result->taskData);
                $this->addFlash('success', 'Подзадача успешно создана.');

                return $this->redirectToRoute('task_show', ['id' => $id]);
            } catch (InvalidArgumentException|LogicException|RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        foreach ($result->errors as $error) {
            $this->addFlash('error', $error);
        }

        return $this->render('task/create.html.twig', [
            'parent' => $parent,
            'taskData' => $result->taskData,
        ]);
    }
}
