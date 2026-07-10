<?php

declare(strict_types=1);

namespace App\Module\Main\Controller;

use App\Module\Main\Exception\PasswordResetException;
use App\Module\Main\Service\ForgotPasswordFormHandler;
use App\Module\Main\Service\PasswordResetService;
use App\Module\Main\Service\ResetPasswordFormHandler;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PasswordResetController extends AbstractController
{
    private const RESET_REQUESTED_MESSAGE = 'Если пользователь с таким email существует, мы отправили ссылку для восстановления пароля.';

    public function __construct(
        private readonly ForgotPasswordFormHandler $forgotPasswordFormHandler,
        private readonly ResetPasswordFormHandler $resetPasswordFormHandler,
        private readonly PasswordResetService $passwordResetService,
    ) {
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request): Response
    {
        $result = $this->forgotPasswordFormHandler->handle($request);

        if (!$result->isSubmitted || !$result->isValid) {
            return $this->render('security/forgot_password.html.twig', [
                'form' => $result->form,
            ]);
        }

        $this->passwordResetService->requestReset($result->data->email);
        $this->addFlash('success', self::RESET_REQUESTED_MESSAGE);

        return $this->redirectToRoute('app_login');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, string $token): Response
    {
        try {
            $this->passwordResetService->assertTokenCanBeUsed($token);
        } catch (PasswordResetException|RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_forgot_password');
        }

        $result = $this->resetPasswordFormHandler->handle($request);

        if (!$result->isSubmitted || !$result->isValid) {
            return $this->render('security/reset_password.html.twig', [
                'form' => $result->form,
            ]);
        }

        try {
            $this->passwordResetService->resetPassword($token, $result->data);
            $this->addFlash('success', 'Пароль успешно изменён. Теперь вы можете войти.');

            return $this->redirectToRoute('app_login');
        } catch (PasswordResetException|RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_forgot_password');
        }
    }
}
