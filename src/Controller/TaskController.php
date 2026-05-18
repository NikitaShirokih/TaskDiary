<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\TaskData;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskRights;
use App\Enum\TaskStatus;
use App\Enum\ToneAi;
use App\Exception\TaskNotFoundException;
use App\Repository\CategoryRepository;
use App\Repository\TaskRepository;
use App\Service\AiService;
use App\Service\TaskExportService;
use App\Service\TaskService;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

#[IsGranted('ROLE_USER')]
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
        $user = $this->getAuthenticatedUser();

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
        $user = $this->getAuthenticatedUser();

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

        return $this->handleTaskForm(
            request: $request,
            template: 'task/create.html.twig',
            onSuccess: function (TaskData $data): Response {
                $this->taskService->addTask($data);
                $this->addFlash('success', 'Задача успешно создана.');

                return $this->redirectToRoute('task_list');
            },
            extra: ['parent' => null],
        );
    }

    #[Route('/{id<\d+>}/subtask/new', name: 'subtask_new', methods: ['GET'])]
    public function newSubtask(int $id): Response
    {
        $parent = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::OWNER->value, $parent);

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

        $this->denyAccessUnlessGranted(TaskRights::OWNER->value, $parent);

        if (!$this->isCsrfTokenValid('subtask_create', (string) $request->request->get('_token'))) {
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

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::OWNER->value, $task);

        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::OWNER->value, $task);

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

    #[Route('/{id<\d+>}/status', name: 'update_status', methods: ['POST'])]
    public function updateStatus(Request $request, int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::OWNER->value, $task);

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

    #[Route('/{id<\d+>}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::OWNER->value, $task);

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

    #[Route('/{id<\d+>}/ai-analyze', name: 'ai_analyze', methods: ['POST'])]
    public function aiAnalyze(Task $task, AiService $aiService): JsonResponse
    {
        $this->denyAccessUnlessGranted(TaskRights::OWNER->value, $task);

        try {
            $result = $aiService->analyzeTask($task);

            return new JsonResponse(['result' => $result]);
        } catch (Throwable $e) {
            return new JsonResponse(
                ['error' => 'Ошибка: '.$e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/ai-improve-description', name: 'ai_improve_description', methods: ['POST'])]
    public function aiImproveDescription(Request $request, AiService $aiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Некорректный JSON.'], Response::HTTP_BAD_REQUEST);
        }

        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $tone = ToneAi::fromMixed($data['tone'] ?? null);

        if ('' === $description) {
            return new JsonResponse(['error' => 'Описание не может быть пустым.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $aiService->improveDescription($title, $description, $tone);

            return new JsonResponse(['result' => $result]);
        } catch (Throwable $e) {
            return new JsonResponse(
                ['error' => 'Ошибка GigaChat: '.$e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id<\d+>}/export', name: 'export_json', methods: ['GET'])]
    public function exportJson(int $id, TaskExportService $exportService): JsonResponse
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::OWNER->value, $task);

        $tasks = $this->taskService->getTaskWithDescendantsForExport($id);

        return new JsonResponse(
            $exportService->exportTask($task, $tasks),
            Response::HTTP_OK,
            ['Content-Disposition' => 'attachment; filename="task_'.$id.'.json"']
        );
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function handleTaskForm(
        Request $request,
        string $template,
        callable $onSuccess,
        array $extra = [],
    ): Response {
        try {
            $taskData = TaskData::fromRequest($request);
        } catch (InvalidArgumentException $e) {
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
        } catch (InvalidArgumentException|LogicException|RuntimeException $e) {
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

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Пользователь не авторизован.');
        }

        return $user;
    }
}
