<?php

declare(strict_types=1);

namespace App\Module\Task\Controller\Api;

use App\Module\Main\Enum\UserRole;
use App\Module\Main\Service\AuthenticatedUserProvider;
use App\Module\Task\Enum\TaskRights;
use App\Module\Task\Exception\TaskNotFoundException;
use App\Module\Task\Service\Api\TaskApiRequestHandler;
use App\Module\Task\Service\Api\TaskApiResponseFactory;
use App\Module\Task\Service\TaskListQueryService;
use App\Module\Task\Service\TaskService;
use InvalidArgumentException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $task = $this->taskService->getTaskById($id);
            $this->denyAccessUnlessGranted(TaskRights::VIEW->value, $task);

            return $this->json(['data' => $this->responseFactory->task($task)]);
        } catch (TaskNotFoundException $e) {
            return $this->json($this->responseFactory->error($e->getMessage()), Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $task = $this->taskService->addTask($this->requestHandler->handleCreate($request));

            return $this->json(['data' => $this->responseFactory->task($task)], Response::HTTP_CREATED);
        } catch (InvalidArgumentException|LogicException $e) {
            return $this->json($this->responseFactory->error($e->getMessage()), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id<\d+>}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $task = $this->taskService->getTaskById($id);
            $this->denyAccessUnlessGranted(TaskRights::EDIT->value, $task);

            $updatedTask = $this->taskService->updateTaskEntity($task, $this->requestHandler->handleUpdate($request));

            return $this->json(['data' => $this->responseFactory->task($updatedTask)]);
        } catch (TaskNotFoundException $e) {
            return $this->json($this->responseFactory->error($e->getMessage()), Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException|LogicException $e) {
            return $this->json($this->responseFactory->error($e->getMessage()), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id<\d+>}/status', name: 'update_status', methods: ['PATCH'])]
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $task = $this->taskService->getTaskById($id);
            $this->denyAccessUnlessGranted(TaskRights::EDIT->value, $task);

            $updatedTask = $this->taskService->updateStatus($task, $this->requestHandler->handleStatus($request));

            return $this->json(['data' => $this->responseFactory->task($updatedTask)]);
        } catch (TaskNotFoundException $e) {
            return $this->json($this->responseFactory->error($e->getMessage()), Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException|LogicException $e) {
            return $this->json($this->responseFactory->error($e->getMessage()), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $task = $this->taskService->getTaskById($id);
            $this->denyAccessUnlessGranted(TaskRights::DELETE->value, $task);

            $this->taskService->deleteTask($task);

            return $this->json(['message' => 'Задача удалена.']);
        } catch (TaskNotFoundException $e) {
            return $this->json($this->responseFactory->error($e->getMessage()), Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/{id<\d+>}/subtasks', name: 'subtask_create', methods: ['POST'])]
    public function createSubtask(Request $request, int $id): JsonResponse
    {
        try {
            $parent = $this->taskService->getTaskById($id);
            $this->denyAccessUnlessGranted(TaskRights::EDIT->value, $parent);

            $subtask = $this->taskService->addSubtaskToTask($parent, $this->requestHandler->handleSubtask($request));

            return $this->json(['data' => $this->responseFactory->task($subtask)], Response::HTTP_CREATED);
        } catch (TaskNotFoundException $e) {
            return $this->json($this->responseFactory->error($e->getMessage()), Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException|LogicException $e) {
            return $this->json($this->responseFactory->error($e->getMessage()), Response::HTTP_BAD_REQUEST);
        }
    }
}
