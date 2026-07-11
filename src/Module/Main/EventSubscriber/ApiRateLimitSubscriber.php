<?php

declare(strict_types=1);

namespace App\Module\Main\EventSubscriber;

use App\Module\Main\Security\ApiRateLimiter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
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
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $this->apiRateLimiter->consume($event->getRequest());
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if (!$throwable instanceof TooManyRequestsHttpException
            || !str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => [
                'message' => $throwable->getMessage(),
                'code' => Response::HTTP_TOO_MANY_REQUESTS,
            ],
        ], Response::HTTP_TOO_MANY_REQUESTS));
    }
}
