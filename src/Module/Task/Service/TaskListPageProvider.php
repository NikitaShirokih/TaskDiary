<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Main\Service\AuthenticatedUserProvider;
use App\Module\Task\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;

final readonly class TaskListPageProvider
{
    public function __construct(
        private TaskListQueryService $taskListQueryService,
        private CategoryRepository $categoryRepository,
        private TaskFilterRequestFactory $filterRequestFactory,
        private AuthenticatedUserProvider $authenticatedUserProvider,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getPageData(Request $request): array
    {
        $filter = $this->filterRequestFactory->createFromRequest($request);
        $user = $this->authenticatedUserProvider->getUser();

        return [
            'tasks' => $this->taskListQueryService->findTasksByView(
                categoryId: $filter->categoryId,
                priority: $filter->priority,
                view: $filter->view,
                user: $user,
            ),
            'categories' => $this->categoryRepository->findByUser($user),
        ];
    }

    /**
     * @return array{tasks: mixed}
     */
    public function getAjaxData(Request $request): array
    {
        $filter = $this->filterRequestFactory->createFromRequest($request);

        return [
            'tasks' => $this->taskListQueryService->findTasksByView(
                categoryId: $filter->categoryId,
                priority: $filter->priority,
                view: $filter->view,
                user: $this->authenticatedUserProvider->getUser(),
            ),
        ];
    }
}
