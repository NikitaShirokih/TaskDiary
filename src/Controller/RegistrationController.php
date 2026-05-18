<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\RegistrationService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationService $registrationService,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $form = $this->createForm(RegistrationFormType::class, new User());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var User $user */
                $user = $form->getData();

                $plainPassword = $form->get('plainPassword')->getData();

                if (!is_string($plainPassword) || '' === trim($plainPassword)) {
                    throw new RuntimeException('Некорректный пароль.');
                }

                $this->registrationService->register($user, $plainPassword);

                $this->addFlash('success', 'Аккаунт успешно создан! Войдите в систему.');

                return $this->redirectToRoute('app_login');
            } catch (RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
        ]);
    }
}
