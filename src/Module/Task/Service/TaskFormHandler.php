<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Task\Dto\TaskFormResult;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class TaskFormHandler
{
    public function __construct(
        private TaskDataFactory $taskDataFactory,
        private ValidatorInterface $validator,
    ) {
    }

    public function handle(Request $request): TaskFormResult
    {
        try {
            $taskData = $this->taskDataFactory->fromArray(
                $request->request->all(),
                'Название задачи обязательно.',
            );
        } catch (InvalidArgumentException $e) {
            return new TaskFormResult(
                taskData: null,
                isSubmitted: true,
                isValid: false,
                errors: [$e->getMessage()],
            );
        }

        $errors = [];

        foreach ($this->validator->validate($taskData) as $error) {
            $errors[] = $error->getMessage();
        }

        if ([] !== $errors) {
            return new TaskFormResult(
                taskData: $taskData,
                isSubmitted: true,
                isValid: false,
                errors: $errors,
            );
        }

        return new TaskFormResult(
            taskData: $taskData,
            isSubmitted: true,
            isValid: true,
            errors: [],
        );
    }
}
