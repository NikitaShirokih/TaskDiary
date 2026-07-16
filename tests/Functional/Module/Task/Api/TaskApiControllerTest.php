<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module\Task\Api;

use App\Module\Main\Entity\User;
use App\Module\Main\Service\ApiTokenService;
use App\Module\Task\Entity\Task;
use App\Module\Task\Enum\TaskStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TaskApiControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();

        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        $this->entityManager->clear();

        parent::tearDown();
    }

    public function testApiTasksRequiresBearerToken(): void
    {
        $this->client->request('GET', '/api/tasks');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $payload = $this->responsePayload();
        self::assertSame(Response::HTTP_UNAUTHORIZED, $payload['error']['code'] ?? null);
        self::assertIsString($payload['error']['message'] ?? null);
    }

    public function testApiTasksListReturnsAuthenticatedUsersTasks(): void
    {
        $user = $this->createVerifiedUser();
        $plainToken = $this->createApiTokenFor($user);
        $task = $this->createTaskFor($user, 'Task from authenticated user');
        $otherTask = $this->createTaskFor($this->createVerifiedUser(), 'Other user task');

        $this->requestWithToken('GET', '/api/tasks', $plainToken);

        self::assertResponseIsSuccessful();
        $tasks = $this->responsePayload()['data'] ?? null;
        self::assertIsArray($tasks);
        self::assertContains($task->getId(), array_column($tasks, 'id'));
        self::assertNotContains($otherTask->getId(), array_column($tasks, 'id'));

        $taskPayload = $this->findTaskPayload($tasks, $task->getId());
        self::assertSame('Task from authenticated user', $taskPayload['title']);
        self::assertSame('waiting', $taskPayload['status']);
        self::assertSame('medium', $taskPayload['priority']);
    }

    public function testCreateTaskWithBearerToken(): void
    {
        $user = $this->createVerifiedUser();
        $plainToken = $this->createApiTokenFor($user);

        $this->requestWithToken('POST', '/api/tasks', $plainToken, json_encode([
            'title' => 'API test task',
            'description' => 'Created from functional test',
            'priority' => 'medium',
            'categoryName' => 'API category',
        ], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $taskPayload = $this->responsePayload()['data'] ?? null;
        self::assertIsArray($taskPayload);
        self::assertSame('API test task', $taskPayload['title']);

        $task = $this->entityManager->getRepository(Task::class)->find($taskPayload['id']);
        self::assertInstanceOf(Task::class, $task);
        self::assertSame($user, $task->getUser());
    }

    public function testCreateTaskWithInvalidJsonReturnsStandardBadRequest(): void
    {
        $user = $this->createVerifiedUser();
        $plainToken = $this->createApiTokenFor($user);

        $this->requestWithToken('POST', '/api/tasks', $plainToken, '{bad json');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $error = $this->responsePayload()['error'] ?? null;
        self::assertIsArray($error);
        self::assertSame(Response::HTTP_BAD_REQUEST, $error['code']);
        self::assertIsString($error['message']);
    }

    public function testShowMissingTaskReturnsStandardNotFound(): void
    {
        $user = $this->createVerifiedUser();
        $plainToken = $this->createApiTokenFor($user);

        $this->requestWithToken('GET', '/api/tasks/999999', $plainToken);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $error = $this->responsePayload()['error'] ?? null;
        self::assertIsArray($error);
        self::assertSame(Response::HTTP_NOT_FOUND, $error['code']);
    }

    public function testUpdateTaskStatusAllowsTransitionFromCompletedToInProgress(): void
    {
        $user = $this->createVerifiedUser();
        $plainToken = $this->createApiTokenFor($user);
        $task = $this->createTaskFor($user, 'Status task');

        $this->requestWithToken('PATCH', '/api/tasks/'.$task->getId().'/status', $plainToken, '{"status":"completed"}');
        self::assertResponseIsSuccessful();
        self::assertSame('completed', $this->responsePayload()['data']['status'] ?? null);

        $this->requestWithToken('PATCH', '/api/tasks/'.$task->getId().'/status', $plainToken, '{"status":"in_progress"}');
        self::assertResponseIsSuccessful();
        self::assertSame('in_progress', $this->responsePayload()['data']['status'] ?? null);

        $persistedTask = $this->entityManager->getRepository(Task::class)->find($task->getId());
        self::assertInstanceOf(Task::class, $persistedTask);
        self::assertSame(TaskStatus::InProgress, $persistedTask->getStatus());
    }

    public function testDeleteTaskReturnsCurrentSuccessResponseAndRemovesTask(): void
    {
        $user = $this->createVerifiedUser();
        $plainToken = $this->createApiTokenFor($user);
        $task = $this->createTaskFor($user, 'Task to delete');
        $taskId = $task->getId();

        $this->requestWithToken('DELETE', '/api/tasks/'.$taskId, $plainToken);

        self::assertResponseIsSuccessful();
        self::assertSame('Задача удалена.', $this->responsePayload()['data']['message'] ?? null);
        self::assertNull($this->entityManager->getRepository(Task::class)->find($taskId));
    }

    private function createVerifiedUser(): User
    {
        $user = (new User())
            ->setEmail(sprintf('api-test-%s@example.com', bin2hex(random_bytes(8))))
            ->setPassword('test-password-hash');
        $user->verifyEmail(new \DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createApiTokenFor(User $user): string
    {
        return self::getContainer()->get(ApiTokenService::class)
            ->createToken($user, 'Functional test token')
            ->plainToken;
    }

    private function createTaskFor(User $user, string $title): Task
    {
        $task = Task::create($user, $title);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    private function requestWithToken(string $method, string $uri, string $plainToken, ?string $content = null): void
    {
        $this->client->request(
            $method,
            $uri,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer '.$plainToken,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $content,
        );
    }

    /** @return array<string, mixed> */
    private function responsePayload(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<int, array<string, mixed>> $tasks
     * @return array<string, mixed>
     */
    private function findTaskPayload(array $tasks, ?int $taskId): array
    {
        foreach ($tasks as $task) {
            if (($task['id'] ?? null) === $taskId) {
                return $task;
            }
        }

        self::fail(sprintf('Task #%s was not found in API response.', (string) $taskId));
    }
}
