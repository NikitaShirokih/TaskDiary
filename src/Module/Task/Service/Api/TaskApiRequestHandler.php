<?php

declare(strict_types=1);

namespace App\Module\Task\Service\Api;

use App\Module\Task\Dto\TaskData;
use App\Module\Task\Enum\TaskStatus;
use App\Module\Task\Service\TaskDataFactory;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

final readonly class TaskApiRequestHandler
{
    public function __construct(
        private TaskDataFactory $taskDataFactory,
    ) {
    }

    public function handleCreate(Request $request): TaskData
    {
        return $this->taskDataFactory->fromArray($this->decodeJson($request));
    }

    public function handleUpdate(Request $request): TaskData
    {
        return $this->taskDataFactory->fromArray($this->decodeJson($request));
    }

    public function handleSubtask(Request $request): TaskData
    {
        return $this->taskDataFactory->fromArray($this->decodeJson($request));
    }

    public function handleStatus(Request $request): TaskStatus
    {
        $payload = $this->decodeJson($request);
        $status = trim((string) ($payload['status'] ?? ''));

        if ($status === '') {
            throw new InvalidArgumentException('Не передан новый статус задачи.');
        }

        return TaskStatus::fromInput($status);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        $content = trim($request->getContent());

        if ($content === '') {
            throw new InvalidArgumentException('Некорректный JSON-запрос.');
        }

        $payload = json_decode($content, true);

        if (!is_array($payload)) {
            throw new InvalidArgumentException('Некорректный JSON-запрос.');
        }

        return $payload;
    }

}
