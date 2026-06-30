<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Entity\Task;
use App\Entity\TaskComment;
use App\Module\User\Enum\UserRole;
use App\Module\Task\Form\TaskCommentFormType;
use App\Module\Task\Service\AuthenticatedUserProvider;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthenticatedUserProvider $authenticatedUserProvider,
    ) {
    }

    #[Route('/{id<\d+>}/comment', name: 'comment_create', methods: ['POST'])]
    #[IsGranted(UserRole::ADMIN->value)]
    public function addComment(Task $task, Request $request): Response
    {
        $comment = new TaskComment();
        $form = $this->createForm(TaskCommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setTask($task);
            $comment->setAuthor($this->authenticatedUserProvider->getUser());

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $this->addFlash('success', 'Коментарий добавлен.');
        }

        return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
    }
}
