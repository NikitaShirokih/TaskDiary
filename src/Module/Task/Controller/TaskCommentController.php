<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Main\Enum\UserRole;
use App\Module\Task\Entity\Task;
use App\Module\Task\Service\TaskCommentFormHandler;
use App\Module\Task\Service\TaskCommentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::USER->value)]
#[Route('/task', name: 'task_')]
final class TaskCommentController extends AbstractController
{
    public function __construct(
        private readonly TaskCommentFormHandler $taskCommentFormHandler,
        private readonly TaskCommentService $taskCommentService,
    ) {
    }

    #[Route('/{id<\d+>}/comment', name: 'comment_create', methods: ['POST'])]
    #[IsGranted(UserRole::ADMIN->value)]
    public function addComment(Task $task, Request $request): Response
    {
        $result = $this->taskCommentFormHandler->handle($request);

        if (!$result->isSubmitted || !$result->isValid) {
            foreach ($result->errors as $error) {
                $this->addFlash('error', $error);
            }

            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        $this->taskCommentService->addComment($task, $result->data);

        $this->addFlash('success', 'Комментарий добавлен.');

        return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
    }
}
