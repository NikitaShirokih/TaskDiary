<?php

declare(strict_types=1);

namespace App\Module\Task\Controller\Api;

use App\Module\Main\Enum\UserRole;
use App\Module\Main\Service\AuthenticatedUserProvider;
use App\Module\Task\Enum\TaskRights;
use App\Module\Task\Service\Api\TaskApiRequestHandler;
use App\Module\Task\Service\Api\TaskApiResponseFactory;
use App\Module\Task\Service\TaskListQueryService;
use App\Module\Task\Service\TaskService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Tasks')]
#[IsGranted(UserRole::USER->value)]
#[Route('/api/tasks', name: 'api_tasks_')]
final class TaskApiController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskListQueryService $taskListQueryService,
        private readonly AuthenticatedUserProvider $authenticatedUserProvider,
        private readonly TaskApiRequestHandler $requestHandler,
        private readonly TaskApiResponseFactory $responseFactory,
    ) {
    }

    #[OA\Get(
        path: '/api/tasks',
        summary: 'Получить список задач',
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'category',
                description: 'ID категории',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'priority',
                description: 'Приоритет задачи',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'medium')
            ),
            new OA\Parameter(
                name: 'view',
                description: 'Фильтр отображения задач',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'active')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Список задач пользователя'),
            new OA\Response(response: 401, description: 'Требуется API token'),
            new OA\Response(response: 429, description: 'Слишком много запросов'),
        ]
    )]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $tasks = $this->taskListQueryService->findTasksByView(
            categoryId: $request->query->getInt('category') > 0 ? $request->query->getInt('category') : null,
            priority: $request->query->getString('priority') !== '' ? $request->query->getString('priority') : null,
            view: $request->query->getString('view', 'active'),
            user: $this->authenticatedUserProvider->getUser(),
        );

        return $this->json([
            'data' => $this->responseFactory->tasks($tasks),
        ]);
    }

    #[OA\Get(
        path: '/api/tasks/{id}',
        summary: 'Получить задачу по ID',
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID задачи',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Данные задачи'),
            new OA\Response(response: 401, description: 'Требуется API token'),
            new OA\Response(response: 403, description: 'Доступ запрещён'),
            new OA\Response(response: 404, description: 'Задача не найдена'),
            new OA\Response(response: 429, description: 'Слишком много запросов'),
        ]
    )]
    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $task = $this->taskService->getTaskById($id);
        $this->denyAccessUnlessGranted(TaskRights::VIEW->value, $task);

        return $this->json(['data' => $this->responseFactory->task($task)]);
    }

    #[OA\Post(
        path: '/api/tasks',
        summary: 'Создать задачу',
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Подготовить отчёт'),
                    new OA\Property(property: 'description', type: 'string', example: 'Собрать данные и отправить руководителю'),
                    new OA\Property(property: 'priority', type: 'string', example: 'medium'),
                    new OA\Property(property: 'categoryName', type: 'string', example: 'Работа'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Задача создана'),
            new OA\Response(response: 400, description: 'Ошибка запроса'),
            new OA\Response(response: 401, description: 'Требуется API token'),
            new OA\Response(response: 429, description: 'Слишком много запросов'),
        ]
    )]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $task = $this->taskService->addTask($this->requestHandler->handleCreate($request));

        return $this->json(['data' => $this->responseFactory->task($task)], Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: '/api/tasks/{id}',
        summary: 'Обновить задачу',
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID задачи',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Обновить отчёт'),
                    new OA\Property(property: 'description', type: 'string', example: 'Обновлённое описание'),
                    new OA\Property(property: 'priority', type: 'string', example: 'high'),
                    new OA\Property(property: 'categoryName', type: 'string', example: 'Работа'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Задача обновлена'),
            new OA\Response(response: 400, description: 'Ошибка запроса'),
            new OA\Response(response: 401, description: 'Требуется API token'),
            new OA\Response(response: 403, description: 'Доступ запрещён'),
            new OA\Response(response: 404, description: 'Задача не найдена'),
            new OA\Response(response: 429, description: 'Слишком много запросов'),
        ]
    )]
    #[Route('/{id<\d+>}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $task = $this->taskService->getTaskById($id);
        $this->denyAccessUnlessGranted(TaskRights::EDIT->value, $task);

        $updatedTask = $this->taskService->updateTaskEntity($task, $this->requestHandler->handleUpdate($request));

        return $this->json(['data' => $this->responseFactory->task($updatedTask)]);
    }

    #[OA\Patch(
        path: '/api/tasks/{id}/status',
        summary: 'Изменить статус задачи',
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID задачи',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'completed'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Статус задачи обновлён'),
            new OA\Response(response: 400, description: 'Некорректный статус'),
            new OA\Response(response: 401, description: 'Требуется API token'),
            new OA\Response(response: 403, description: 'Доступ запрещён'),
            new OA\Response(response: 404, description: 'Задача не найдена'),
            new OA\Response(response: 429, description: 'Слишком много запросов'),
        ]
    )]
    #[Route('/{id<\d+>}/status', name: 'update_status', methods: ['PATCH'])]
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $task = $this->taskService->getTaskById($id);
        $this->denyAccessUnlessGranted(TaskRights::EDIT->value, $task);

        $updatedTask = $this->taskService->updateStatus($task, $this->requestHandler->handleStatus($request));

        return $this->json(['data' => $this->responseFactory->task($updatedTask)]);
    }

    #[OA\Delete(
        path: '/api/tasks/{id}',
        summary: 'Удалить задачу',
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID задачи',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Задача удалена'),
            new OA\Response(response: 401, description: 'Требуется API token'),
            new OA\Response(response: 403, description: 'Доступ запрещён'),
            new OA\Response(response: 404, description: 'Задача не найдена'),
            new OA\Response(response: 429, description: 'Слишком много запросов'),
        ]
    )]
    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $task = $this->taskService->getTaskById($id);
        $this->denyAccessUnlessGranted(TaskRights::DELETE->value, $task);

        $this->taskService->deleteTask($task);

        return $this->json(['data' => ['message' => 'Задача удалена.']]);
    }

    #[OA\Post(
        path: '/api/tasks/{id}/subtasks',
        summary: 'Создать подзадачу',
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID родительской задачи',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Проверить цифры'),
                    new OA\Property(property: 'description', type: 'string', example: 'Проверить итоговые значения в отчёте'),
                    new OA\Property(property: 'priority', type: 'string', example: 'high'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Подзадача создана'),
            new OA\Response(response: 400, description: 'Ошибка запроса'),
            new OA\Response(response: 401, description: 'Требуется API token'),
            new OA\Response(response: 403, description: 'Доступ запрещён'),
            new OA\Response(response: 404, description: 'Родительская задача не найдена'),
            new OA\Response(response: 429, description: 'Слишком много запросов'),
        ]
    )]
    #[Route('/{id<\d+>}/subtasks', name: 'subtask_create', methods: ['POST'])]
    public function createSubtask(Request $request, int $id): JsonResponse
    {
        $parent = $this->taskService->getTaskById($id);
        $this->denyAccessUnlessGranted(TaskRights::EDIT->value, $parent);

        $subtask = $this->taskService->addSubtaskToTask($parent, $this->requestHandler->handleSubtask($request));

        return $this->json(['data' => $this->responseFactory->task($subtask)], Response::HTTP_CREATED);
    }
}