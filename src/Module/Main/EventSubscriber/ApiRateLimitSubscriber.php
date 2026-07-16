<?php

declare(strict_types=1);

namespace App\Module\Main\EventSubscriber;

use App\Module\Main\Security\ApiRateLimiter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ApiRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ApiRateLimiter $apiRateLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $this->apiRateLimiter->consume($event->getRequest());
    }
}
