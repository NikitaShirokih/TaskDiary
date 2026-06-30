<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Task\Dto\TaskData;
use App\Module\Task\Enum\TaskRights;
use App\Module\Main\Enum\UserRole;
use App\Module\Task\Service\TaskFormHandler;
use App\Module\Task\Repository\CategoryRepository;
use App\Module\Task\Service\TaskService;
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
        private readonly CategoryRepository $categoryRepository,
        private readonly TaskFormHandler $taskFormHandler,
    ) {
    }

    #[Route('/{id<\d+>}/subtask/new', name: 'subtask_new', methods: ['GET'])]
    public function newSubtask(int $id): Response
    {
        $parent = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::EDIT->value, $parent);

        return $this->render('task/create.html.twig', [
            'categories' => $this->categoryRepository->findAll(),
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

        $result = $this->taskFormHandler->handle(
            $request,
            function (TaskData $data) use ($id): Response {
                $this->taskService->addSubtask($id, $data);
                $this->addFlash('success', 'Подзадача успешно создана.');

                return $this->redirectToRoute('task_show', ['id' => $id]);
            },
        );

        if (null !== $result->response) {
            return $result->response;
        }

        foreach ($result->errors as $error) {
            $this->addFlash('error', $error);
        }

        return $this->render('task/create.html.twig', [
            'parent' => $parent,
            'categories' => $this->categoryRepository->findAll(),
            'taskData' => $result->taskData,
        ]);
    }
}
