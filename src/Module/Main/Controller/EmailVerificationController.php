<?php

declare(strict_types=1);

namespace App\Module\Main\Controller;

use App\Module\Main\Service\EmailVerificationService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EmailVerificationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService,
    ) {
    }

    #[Route('/email/verify/{token}', name: 'email_verify', methods: ['GET'])]
    public function verify(string $token): Response
    {
        try {
            $this->emailVerificationService->verify($token);

            $this->addFlash('success', 'Email успешно подтверждён. Теперь вы можете войти.');
        } catch (RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_login');
    }
}
