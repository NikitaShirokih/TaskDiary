<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

use App\Module\Main\Dto\RegistrationData;
use App\Module\Main\Dto\RegistrationFormResult;
use App\Module\Main\Form\RegistrationFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class RegistrationFormHandler
{
    public function __construct(
        private FormFactoryInterface $formFactory,
    ) {
    }

    public function handle(Request $request): RegistrationFormResult
    {
        $data = new RegistrationData();
        $form = $this->formFactory->create(RegistrationFormType::class, $data);

        $form->handleRequest($request);

        return new RegistrationFormResult(
            form: $form->createView(),
            data: $data,
            isSubmitted: $form->isSubmitted(),
            isValid: $form->isSubmitted() && $form->isValid(),
        );
    }
}
