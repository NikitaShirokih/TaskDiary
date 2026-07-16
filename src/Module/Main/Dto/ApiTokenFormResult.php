<?php

declare(strict_types=1);

namespace App\Module\Main\Dto;

use Symfony\Component\Form\FormView;

final readonly class ApiTokenFormResult
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        public ApiTokenData $data,
        public FormView $formView,
        public bool $isSubmitted,
        public bool $isValid,
        public array $errors = [],
    ) {
    }
}
