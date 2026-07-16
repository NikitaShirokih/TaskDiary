<?php

declare(strict_types=1);

namespace App\Module\Task\Dto;

use Symfony\Component\Form\FormView;

final readonly class TaskCommentFormResult
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        public TaskCommentData $data,
        public FormView $formView,
        public bool $isSubmitted,
        public bool $isValid,
        public array $errors = [],
    ) {
    }
}
