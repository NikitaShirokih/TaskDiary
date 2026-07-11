<?php

declare(strict_types=1);

namespace App\Module\Main\Controller\Profile;

use App\Module\Main\Dto\ApiTokenData;
use App\Module\Main\Enum\UserRole;
use App\Module\Main\Form\ApiTokenFormType;
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
        private readonly ApiTokenService $apiTokenService,
        private readonly AuthenticatedUserProvider $authenticatedUserProvider,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->renderPage(new ApiTokenData());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $data = new ApiTokenData();
        $form = $this->createForm(ApiTokenFormType::class, $data);
        $form->handleRequest($request);
        $createdToken = null;

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $result = $this->apiTokenService->createToken(
                    $this->authenticatedUserProvider->getUser(),
                    $data->name,
                );
                $createdToken = $result->plainToken;
                $data = new ApiTokenData();
                $form = $this->createForm(ApiTokenFormType::class, $data);
                $this->addFlash('success', 'API token создан.');
            } catch (InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->renderPage($data, $createdToken, $form->createView());
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

    private function renderPage(ApiTokenData $data, ?string $createdToken = null, ?FormView $formView = null): Response
    {
        $user = $this->authenticatedUserProvider->getUser();
        $formView ??= $this->createForm(ApiTokenFormType::class, $data)->createView();

        return $this->render('profile/api_tokens.html.twig', [
            'form' => $formView,
            'tokens' => $this->apiTokenService->getActiveTokens($user),
            'created_token' => $createdToken,
        ]);
    }
}
