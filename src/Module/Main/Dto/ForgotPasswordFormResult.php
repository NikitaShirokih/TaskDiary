<?php

declare(strict_types=1);

namespace App\Module\Main\Dto;

use Symfony\Component\Form\FormView;

final readonly class ForgotPasswordFormResult
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        public FormView $form,
        public ForgotPasswordData $data,
        public bool $isSubmitted,
        public bool $isValid,
        public array $errors = [],
    ) {
    }
}
