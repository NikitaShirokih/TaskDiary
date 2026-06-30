<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Dto\TaskData;
use App\Module\Task\Dto\TaskFormResult;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class TaskFormHandler
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @param callable(TaskData): Response $onSuccess
     */
    public function handle(Request $request, callable $onSuccess): TaskFormResult
    {
        try {
            $taskData = TaskData::fromRequest($request);
        } catch (InvalidArgumentException $e) {
            return TaskFormResult::failure(null, [$e->getMessage()]);
        }

        $errors = [];

        foreach ($this->validator->validate($taskData) as $error) {
            $errors[] = $error->getMessage();
        }

        if ([] !== $errors) {
            return TaskFormResult::failure($taskData, $errors);
        }

        try {
            return TaskFormResult::success($onSuccess($taskData));
        } catch (InvalidArgumentException|LogicException|RuntimeException $e) {
            return TaskFormResult::failure($taskData, [$e->getMessage()]);
        }
    }
}
