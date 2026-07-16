<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Task\Enum\TaskStatus;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

final readonly class TaskStatusRequestHandler
{
    public function handle(Request $request): TaskStatus
    {
        $statusValue = trim((string) $request->request->get('status', ''));

        if ('' === $statusValue) {
            throw new InvalidArgumentException('Не передан новый статус задачи.');
        }

        return TaskStatus::fromInput($statusValue);
    }
}
