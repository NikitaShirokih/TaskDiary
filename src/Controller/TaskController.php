<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\TaskData;
use App\Entity\Task;
use App\Entity\User;
use App\Exception\TaskNotFoundException;
use App\Repository\CategoryRepository;
use App\Repository\TaskRepository;
use App\Service\AiService;
use App\Service\TaskExportService;
use App\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/task', name: 'task_')]
final class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskRepository $taskRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        $tasks = $this->taskRepository->findTasksByView(
            categoryId: $this->extractCategoryId($request),
            priority: $request->query->get('priority'),
            view: $request->query->getString('view', 'active'),
            user: $user,
        );

        return $this->render('task/list.html.twig', [
            'tasks' => $tasks,
            'categories' => $this->categoryRepository->findAll(),
        ]);
    }

    #[Route('/ajax/list', name: 'ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        $tasks = $this->taskRepository->findTasksByView(
            categoryId: $this->extractCategoryId($request),
            priority: $request->query->get('priority'),
            view: $request->query->getString('view', 'active'),
            user: $user,
        );

        return $this->render('task/_tasks_table.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('task_create', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Недействительный CSRF-токен.');
            }

            return $this->handleTaskForm(
                request: $request,
                template: 'task/create.html.twig',
                onSuccess: function (TaskData $data): Response {
                    $this->taskService->addTask($data);
                    $this->addFlash('success', 'Задача успешно создана.');

                    return $this->redirectToRoute('task_list');
                },
            );
        }

        return $this->render('task/create.html.twig', [
            'categories' => $this->categoryRepository->findAll(),
            'taskData' => null,
            'parent' => null,
        ]);
    }

    #[Route('/analytics', name: 'analytics', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function analytics(TaskRepository $taskRepository): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        $productivityByDays = $taskRepository->getProductivityByDays($user);
        $getAvgCompletionDays = $taskRepository->getAvgCompletionByDays($user);
        $getBurndownDays = $taskRepository->getBurndownData($user);

        return $this->render('task/analytics.html.twig', [
            'tasks' => $taskRepository->findTasksByView(user: $user),
            'categories' => $this->categoryRepository->findAll(),
            'productivityByDays' => $productivityByDays,
            'avgCompletionDays' => $getAvgCompletionDays,
            'burndownDays' => $getBurndownDays,
        ]);
    }

    #[Route('/{id}/subtask/create', name: 'subtask_create', methods: ['GET', 'POST'])]
    public function createSubtask(Request $request, int $id): Response
    {
        $parent = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted('TASK_OWNER', $parent);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('subtask_create', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Недействительный CSRF-токен.');
            }

            return $this->handleTaskForm(
                request: $request,
                template: 'task/create.html.twig',
                onSuccess: function (TaskData $data) use ($id): Response {
                    $this->taskService->addSubtask($id, $data);
                    $this->addFlash('success', 'Подзадача успешно создана.');

                    return $this->redirectToRoute('task_show', ['id' => $id]);
                },
                extra: ['parent' => $parent],
            );
        }

        return $this->render('task/create.html.twig', [
            'categories' => $this->categoryRepository->findAll(),
            'taskData' => null,
            'parent' => $parent,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Request $request): Response
    {
        $id = (int) $request->attributes->get('id');
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted('TASK_OWNER', $task);

        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted('TASK_OWNER', $task);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('task_edit', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Недействительный CSRF-токен.');
            }

            return $this->handleTaskForm(
                request: $request,
                template: 'task/edit.html.twig',
                onSuccess: function (TaskData $data) use ($id): Response {
                    $this->taskService->updateTask($id, $data);
                    $this->addFlash('success', 'Задача успешно обновлена.');

                    return $this->redirectToRoute('task_list');
                },
                extra: ['task' => $task],
            );
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'categories' => $this->categoryRepository->findAll(),
            'taskData' => null,
        ]);
    }

    #[Route('/{id}/status', name: 'update_status', methods: ['POST'])]
    public function updateStatus(Request $request, int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted('TASK_OWNER', $task);

        try {
            $this->taskService->updateStatus(
                id: $task->getId(),
                status: (string) $request->request->get('status'),
            );
            $this->addFlash('success', 'Статус задачи обновлён.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('task_list');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted('TASK_OWNER', $task);

        if (!$this->isCsrfTokenValid('task-delete-'.$id, $request->request->get('_token'))) {
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

    /**
     * @param array<string, mixed> $extra
     */
    private function handleTaskForm(Request $request, string $template, callable $onSuccess, array $extra = []): Response
    {
        try {
            $taskData = TaskData::fromRequest($request);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->render($template, $extra + [
                'categories' => $this->categoryRepository->findAll(),
                'taskData' => null,
            ]);
        }

        $errors = $this->validator->validate($taskData);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }

            return $this->render($template, $extra + [
                'categories' => $this->categoryRepository->findAll(),
                'taskData' => $taskData,
            ]);
        }

        try {
            return $onSuccess($taskData);
        } catch (\LogicException|\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->render($template, $extra + [
                'categories' => $this->categoryRepository->findAll(),
                'taskData' => $taskData,
            ]);
        }
    }

    private function extractCategoryId(Request $request): ?int
    {
        $value = $request->query->get('category');

        return null !== $value && '' !== $value ? (int) $value : null;
    }

    #[Route('/{id}/ai-analyze', name: 'ai_analyze', methods: ['POST'])]
    public function aiAnalyze(Task $task, AiService $aiService): JsonResponse
    {
        $this->denyAccessUnlessGranted('TASK_OWNER', $task);

        try {
            $result = $aiService->analyzeTask(
                $task->getTitle(),
                $task->getDescription()
            );

            return new JsonResponse(['result' => $result]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Ошибка: '.$e->getMessage()],
                500
            );
        }
    }

    #[Route('/ai-improve-description', name: 'ai_improve_description', methods: ['POST'])]
    public function aiImproveDescription(Request $request, AiService $aiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $tone = in_array($data['tone'] ?? '', ['friendly', 'angry', 'neutral'])
            ? $data['tone']
            : 'neutral';

        if ('' === $description) {
            return new JsonResponse(['error' => 'Описание не может быть пустым'], 400);
        }

        try {
            $result = $aiService->improveDescription($title, $description, $tone);

            return new JsonResponse(['result' => $result]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Ошибка GigaChat: '.$e->getMessage()], 500);
        }
    }

    #[Route('/{id}/export', name: 'export_json', methods: ['GET'])]
    public function exportJson(int $id, TaskExportService $exportService): JsonResponse
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted('TASK_OWNER', $task);

        return new JsonResponse(
            $exportService->serializeTask($task),
            200,
            ['Content-Disposition' => 'attachment; filename="task_'.$id.'.json"']
        );
    }
}
