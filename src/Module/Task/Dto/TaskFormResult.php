<?php

declare(strict_types=1);

namespace App\Module\Task\Dto;

use App\Module\Task\Dto\TaskData;
use Symfony\Component\HttpFoundation\Response;

final readonly class TaskFormResult
{
    /**
     * @param list<string> $errors
     */
    private function __construct(
        public ?Response $response,
        public ?TaskData $taskData,
        public array $errors,
    ) {
    }

    public static function success(Response $response): self
    {
        return new self($response, null, []);
    }

    /**
     * @param list<string> $errors
     */
    public static function failure(?TaskData $taskData, array $errors): self
    {
        return new self(null, $taskData, $errors);
    }
}
