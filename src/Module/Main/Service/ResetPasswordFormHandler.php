<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

use App\Module\Main\Dto\ResetPasswordData;
use App\Module\Main\Dto\ResetPasswordFormResult;
use App\Module\Main\Form\ResetPasswordFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ResetPasswordFormHandler
{
    public function __construct(
        private FormFactoryInterface $formFactory,
    ) {
    }

    public function handle(Request $request): ResetPasswordFormResult
    {
        $data = new ResetPasswordData();
        $form = $this->formFactory->create(ResetPasswordFormType::class, $data);

        $form->handleRequest($request);

        return new ResetPasswordFormResult(
            form: $form->createView(),
            data: $data,
            isSubmitted: $form->isSubmitted(),
            isValid: $form->isSubmitted() && $form->isValid(),
        );
    }
}
