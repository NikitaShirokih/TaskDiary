<?php

declare(strict_types=1);

namespace App\Module\Main\Dto;

use Symfony\Component\Form\FormView;

final readonly class RegistrationFormResult
{
    public function __construct(
        public FormView $form,
        public RegistrationData $data,
        public bool $isSubmitted,
        public bool $isValid,
    ) {
    }
}
