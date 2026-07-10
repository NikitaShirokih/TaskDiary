<?php

declare(strict_types=1);

namespace App\Module\Main\Controller;

use App\Module\Main\Service\RegistrationFormHandler;
use App\Module\Main\Service\RegistrationService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationFormHandler $registrationFormHandler,
        private readonly RegistrationService $registrationService,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $result = $this->registrationFormHandler->handle($request);

        if (!$result->isSubmitted || !$result->isValid) {
            return $this->render('registration/register.html.twig', [
                'form' => $result->form,
            ]);
        }

        try {
            $this->registrationService->register($result->data);

            $this->addFlash('success', 'Аккаунт успешно создан. Проверьте email для подтверждения регистрации.');

            return $this->redirectToRoute('app_login');
        } catch (RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->render('registration/register.html.twig', [
                'form' => $result->form,
            ]);
        }
    }
}
