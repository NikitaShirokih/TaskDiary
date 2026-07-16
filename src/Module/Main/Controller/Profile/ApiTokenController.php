<?php

declare(strict_types=1);

namespace App\Module\Main\Controller\Profile;

use App\Module\Main\Enum\UserRole;
use App\Module\Main\Service\ApiTokenFormHandler;
use App\Module\Main\Service\ApiTokenService;
use App\Module\Main\Service\AuthenticatedUserProvider;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::USER->value)]
#[Route('/profile/api-tokens', name: 'profile_api_tokens_')]
final class ApiTokenController extends AbstractController
{
    public function __construct(
        private readonly ApiTokenFormHandler $apiTokenFormHandler,
        private readonly ApiTokenService $apiTokenService,
        private readonly AuthenticatedUserProvider $authenticatedUserProvider,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->renderPage($this->apiTokenFormHandler->createEmptyResult()->formView);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $formResult = $this->apiTokenFormHandler->handle($request);
        $createdToken = null;

        if ($formResult->isSubmitted && $formResult->isValid) {
            try {
                $result = $this->apiTokenService->createToken(
                    $this->authenticatedUserProvider->getUser(),
                    $formResult->data->name,
                );
                $createdToken = $result->plainToken;
                $formResult = $this->apiTokenFormHandler->createEmptyResult();
                $this->addFlash('success', 'API token создан.');
            } catch (InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        foreach ($formResult->errors as $error) {
            $this->addFlash('error', $error);
        }

        return $this->renderPage($formResult->formView, $createdToken);
    }

    #[Route('/{id<\d+>}/revoke', name: 'revoke', methods: ['POST'])]
    public function revoke(Request $request, int $id): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('api_token_revoke_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Некорректный CSRF token.');

            return $this->redirectToRoute('profile_api_tokens_index');
        }

        try {
            $this->apiTokenService->revokeToken($this->authenticatedUserProvider->getUser(), $id);
            $this->addFlash('success', 'API token отозван.');
        } catch (InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('profile_api_tokens_index');
    }

    private function renderPage(FormView $formView, ?string $createdToken = null): Response
    {
        $user = $this->authenticatedUserProvider->getUser();

        return $this->render('profile/api_tokens.html.twig', [
            'form' => $formView,
            'tokens' => $this->apiTokenService->getActiveTokens($user),
            'created_token' => $createdToken,
        ]);
    }
}
