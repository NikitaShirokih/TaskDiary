<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Dto\TaskData;
use App\Enum\TaskRights;
use App\Enum\UserRole;
use App\Module\Task\Service\TaskFormHandler;
use App\Repository\CategoryRepository;
use App\Service\TaskService;
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
        private readonly CategoryRepository $categoryRepository,
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
                'categories' => $this->categoryRepository->findAll(),
                'taskData' => null,
            ]);
        }

        if (!$this->isCsrfTokenValid('task_edit', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Недействительный CSRF-токен.');
        }

        $result = $this->taskFormHandler->handle(
            $request,
            function (TaskData $data) use ($id): Response {
                $this->taskService->updateTask($id, $data);
                $this->addFlash('success', 'Задача успешно обновлена.');

                return $this->redirectToRoute('task_list');
            },
        );

        if (null !== $result->response) {
            return $result->response;
        }

        foreach ($result->errors as $error) {
            $this->addFlash('error', $error);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'categories' => $this->categoryRepository->findAll(),
            'taskData' => $result->taskData,
        ]);
    }
}
