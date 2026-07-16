<?php

declare(strict_types=1);

namespace App\Module\Task\Service;

use App\Module\Task\Dto\TaskCommentData;
use App\Module\Task\Dto\TaskCommentFormResult;
use App\Module\Task\Form\TaskCommentFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class TaskCommentFormHandler
{
    public function __construct(
        private FormFactoryInterface $formFactory,
    ) {
    }

    public function handle(Request $request): TaskCommentFormResult
    {
        $data = new TaskCommentData();
        $form = $this->formFactory->create(TaskCommentFormType::class, $data);

        $form->handleRequest($request);

        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new TaskCommentFormResult(
            data: $data,
            formView: $form->createView(),
            isSubmitted: $form->isSubmitted(),
            isValid: $form->isSubmitted() && $form->isValid(),
            errors: $errors,
        );
    }
}
