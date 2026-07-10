<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Main\Enum\UserRole;
use App\Module\Task\Dto\TaskCommentData;
use App\Module\Task\Entity\Task;
use App\Module\Task\Form\TaskCommentFormType;
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
        private readonly TaskCommentService $taskCommentService,
    ) {
    }

    #[Route('/{id<\d+>}/comment', name: 'comment_create', methods: ['POST'])]
    #[IsGranted(UserRole::ADMIN->value)]
    public function addComment(Task $task, Request $request): Response
    {
        $form = $this->createForm(TaskCommentFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TaskCommentData $data */
            $data = $form->getData();

            $this->taskCommentService->addComment($task, $data);

            $this->addFlash('success', 'Комментарий добавлен.');
        }

        return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
    }
}
