<?php

declare(strict_types=1);

namespace App\Module\Main\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuthenticatedUserRedirectSubscriber implements EventSubscriberInterface
{
    private const ANONYMOUS_ONLY_ROUTES = [
        'app_login',
        'app_register',
    ];

    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (!is_string($route)) {
            return;
        }

        if (!in_array($route, self::ANONYMOUS_ONLY_ROUTES, true)) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        $event->setResponse(
            new RedirectResponse(
                $this->urlGenerator->generate('app_dashboard')
            )
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -10],
        ];
    }
}
