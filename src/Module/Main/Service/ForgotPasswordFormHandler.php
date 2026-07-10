<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

use App\Module\Main\Dto\ForgotPasswordData;
use App\Module\Main\Dto\ForgotPasswordFormResult;
use App\Module\Main\Form\ForgotPasswordFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ForgotPasswordFormHandler
{
    public function __construct(
        private FormFactoryInterface $formFactory,
    ) {
    }

    public function handle(Request $request): ForgotPasswordFormResult
    {
        $data = new ForgotPasswordData();
        $form = $this->formFactory->create(ForgotPasswordFormType::class, $data);

        $form->handleRequest($request);

        return new ForgotPasswordFormResult(
            form: $form->createView(),
            data: $data,
            isSubmitted: $form->isSubmitted(),
            isValid: $form->isSubmitted() && $form->isValid(),
        );
    }
}
