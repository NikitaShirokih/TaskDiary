<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Main\Enum\UserRole;
use App\Module\Task\Service\TaskListQueryService;
use App\Module\Main\Service\AuthenticatedUserProvider;
use App\Module\Task\Service\TaskFilterRequestFactory;
use App\Module\Task\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::USER->value)]
#[Route('/task', name: 'task_')]
final class TaskListController extends AbstractController
{
    public function __construct(
        private readonly TaskListQueryService $taskListQueryService,
        private readonly CategoryRepository $categoryRepository,
        private readonly TaskFilterRequestFactory $filterRequestFactory,
        private readonly AuthenticatedUserProvider $authenticatedUserProvider,
    ) {
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $filter = $this->filterRequestFactory->createFromRequest($request);
        $user = $this->authenticatedUserProvider->getUser();

        $tasks = $this->taskListQueryService->findTasksByView(
            categoryId: $filter->categoryId,
            priority: $filter->priority,
            view: $filter->view,
            user: $user,
        );

        return $this->render('task/list.html.twig', [
            'tasks' => $tasks,
            'categories' => $this->categoryRepository->findByUser($user),
        ]);
    }

    #[Route('/ajax/list', name: 'ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request): Response
    {
        $filter = $this->filterRequestFactory->createFromRequest($request);

        $tasks = $this->taskListQueryService->findTasksByView(
            categoryId: $filter->categoryId,
            priority: $filter->priority,
            view: $filter->view,
            user: $this->authenticatedUserProvider->getUser(),
        );

        return $this->render('task/_tasks_table.html.twig', [
            'tasks' => $tasks,
        ]);
    }
}
