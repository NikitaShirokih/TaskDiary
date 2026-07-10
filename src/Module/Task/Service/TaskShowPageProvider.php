<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Main\Enum\UserRole;
use App\Module\Task\Entity\Task;
use App\Module\Task\Form\TaskCommentFormType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class TaskShowPageProvider
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return array{task: Task, commentForm: FormView|null}
     */
    public function getPageData(Task $task): array
    {
        return [
            'task' => $task,
            'commentForm' => $this->createCommentForm($task),
        ];
    }

    private function createCommentForm(Task $task): ?FormView
    {
        if (!$this->security->isGranted(UserRole::ADMIN->value)) {
            return null;
        }

        return $this->formFactory->create(TaskCommentFormType::class, null, [
            'action' => $this->urlGenerator->generate('task_comment_create', ['id' => $task->getId()]),
            'method' => 'POST',
        ])->createView();
    }
}
