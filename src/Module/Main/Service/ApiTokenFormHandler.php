<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

use App\Module\Main\Dto\ApiTokenData;
use App\Module\Main\Dto\ApiTokenFormResult;
use App\Module\Main\Form\ApiTokenFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ApiTokenFormHandler
{
    public function __construct(
        private FormFactoryInterface $formFactory,
    ) {
    }

    public function handle(Request $request): ApiTokenFormResult
    {
        $data = new ApiTokenData();
        $form = $this->formFactory->create(ApiTokenFormType::class, $data);

        $form->handleRequest($request);

        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new ApiTokenFormResult(
            data: $data,
            formView: $form->createView(),
            isSubmitted: $form->isSubmitted(),
            isValid: $form->isSubmitted() && $form->isValid(),
            errors: $errors,
        );
    }

    public function createEmptyResult(): ApiTokenFormResult
    {
        $data = new ApiTokenData();
        $form = $this->formFactory->create(ApiTokenFormType::class, $data);

        return new ApiTokenFormResult(
            data: $data,
            formView: $form->createView(),
            isSubmitted: false,
            isValid: false,
        );
    }
}
