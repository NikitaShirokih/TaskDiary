<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Main\Enum\UserRole;
use App\Module\Task\Dto\TaskData;
use App\Module\Task\Enum\TaskRights;
use App\Module\Task\Service\TaskFormHandler;
use App\Module\Task\Service\TaskService;
use App\Module\Task\Service\TaskStatusRequestHandler;
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
final class TaskCrudController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskFormHandler $taskFormHandler,
        private readonly TaskStatusRequestHandler $taskStatusRequestHandler,
    ) {
    }

    #[Route('/new', name: 'new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('task/create.html.twig', [
            'taskData' => null,
            'parent' => null,
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('task_create', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Недействительный CSRF-токен.');
        }

        $result = $this->taskFormHandler->handle($request);

        if ($result->isValid && $result->taskData instanceof TaskData) {
            try {
                $this->taskService->addTask($result->taskData);
                $this->addFlash('success', 'Задача успешно создана.');

                return $this->redirectToRoute('task_list');
            } catch (InvalidArgumentException|LogicException|RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        foreach ($result->errors as $error) {
            $this->addFlash('error', $error);
        }

        return $this->render('task/create.html.twig', [
            'parent' => null,
            'taskData' => $result->taskData,
        ]);
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

    #[Route('/{id<\d+>}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::DELETE->value, $task);

        if (!$this->isCsrfTokenValid('task-delete-'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Недействительный CSRF-токен.');
        }

        $this->taskService->deleteTask($task);
        $this->addFlash('success', 'Задача успешно удалена.');

        return $this->redirectToRoute('task_list');
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
