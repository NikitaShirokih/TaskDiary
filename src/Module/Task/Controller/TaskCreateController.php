<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Task\Dto\TaskData;
use App\Module\User\Enum\UserRole;
use App\Module\Task\Service\TaskFormHandler;
use App\Module\Category\Repository\CategoryRepository;
use App\Module\Task\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::USER->value)]
#[Route('/task', name: 'task_')]
final class TaskCreateController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly CategoryRepository $categoryRepository,
        private readonly TaskFormHandler $taskFormHandler,
    ) {
    }

    #[Route('/new', name: 'new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('task/create.html.twig', [
            'categories' => $this->categoryRepository->findAll(),
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

        $result = $this->taskFormHandler->handle(
            $request,
            function (TaskData $data): Response {
                $this->taskService->addTask($data);
                $this->addFlash('success', 'Задача успешно создана.');

                return $this->redirectToRoute('task_list');
            },
        );

        if (null !== $result->response) {
            return $result->response;
        }

        foreach ($result->errors as $error) {
            $this->addFlash('error', $error);
        }

        return $this->render('task/create.html.twig', [
            'parent' => null,
            'categories' => $this->categoryRepository->findAll(),
            'taskData' => $result->taskData,
        ]);
    }
}
