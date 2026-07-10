<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

use App\Module\Main\Repository\UserRepository;
use App\Module\Task\Repository\TaskRepository;

final readonly class AdminDashboardQueryService
{
    public function __construct(
        private TaskRepository $taskRepository,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getTasksPageData(): array
    {
        return [
            'tasks' => $this->taskRepository->findAll(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUsersPageData(): array
    {
        return [
            'users' => $this->userRepository->findAll(),
        ];
    }
}
