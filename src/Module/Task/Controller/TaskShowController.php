<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Entity\TaskComment;
use App\Enum\TaskRights;
use App\Enum\UserRole;
use App\Form\TaskCommentFormType;
use App\Service\TaskService;
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
