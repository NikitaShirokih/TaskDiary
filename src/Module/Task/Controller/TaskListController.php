<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Main\Enum\UserRole;
use App\Module\Task\Service\TaskListPageProvider;
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
        private readonly TaskListPageProvider $taskListPageProvider,
    ) {
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        return $this->render('task/list.html.twig', $this->taskListPageProvider->getPageData($request));
    }

    #[Route('/ajax/list', name: 'ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request): Response
    {
        return $this->render('task/_tasks_table.html.twig', $this->taskListPageProvider->getAjaxData($request));
    }
}
