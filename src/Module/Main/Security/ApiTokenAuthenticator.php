<?php

declare(strict_types=1);

namespace App\Module\Main\Security;

use App\Module\Main\Entity\ApiToken;
use App\Module\Main\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly ApiTokenRepository $apiTokenRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SecureTokenGenerator $secureTokenGenerator,
    ) {
    }

    public function supports(Request $request): bool
    {
    $path = $request->getPathInfo();

    if (str_starts_with($path, '/api/doc')) {
        return false;
    }

    return str_starts_with($path, '/api');
    }

    public function authenticate(Request $request): Passport
    {
        $authorizationHeader = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorizationHeader, $matches)) {
            throw new CustomUserMessageAuthenticationException('Требуется API token.');
        }

        $plainToken = trim($matches[1]);

        if ($plainToken === '') {
            throw new CustomUserMessageAuthenticationException('Требуется API token.');
        }

        $tokenHash = $this->secureTokenGenerator->hashToken($plainToken);

        return new SelfValidatingPassport(
            new UserBadge($tokenHash, function (string $tokenHash): UserInterface {
                $apiToken = $this->apiTokenRepository->findActiveByTokenHash($tokenHash);

                if (!$apiToken instanceof ApiToken) {
                    throw new CustomUserMessageAuthenticationException('API token недействителен.');
                }

                $apiToken->markUsed(new \DateTimeImmutable());
                $this->entityManager->flush();

                return $apiToken->getUser();
            }),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->errorResponse($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->errorResponse('Требуется API token.', Response::HTTP_UNAUTHORIZED);
    }

    private function errorResponse(string $message, int $statusCode): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'message' => $message,
                'code' => $statusCode,
            ],
        ], $statusCode);
    }
}
