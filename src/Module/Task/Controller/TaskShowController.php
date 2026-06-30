<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Task\Entity\TaskComment;
use App\Module\Task\Enum\TaskRights;
use App\Module\Main\Enum\UserRole;
use App\Module\Task\Form\TaskCommentFormType;
use App\Module\Task\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::USER->value)]
#[Route('/task', name: 'task_')]
final class TaskShowController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {
    }

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $task = $this->taskService->getTaskById($id);

        $this->denyAccessUnlessGranted(TaskRights::VIEW->value, $task);

        $commentForm = null;

        if ($this->isGranted(UserRole::ADMIN->value)) {
            $commentForm = $this->createForm(TaskCommentFormType::class, new TaskComment(), [
                'action' => $this->generateUrl('task_comment_create', ['id' => $task->getId()]),
                'method' => 'POST',
            ])->createView();
        }

        return $this->render('task/show.html.twig', [
            'task' => $task,
            'commentForm' => $commentForm,
        ]);
    }
}
