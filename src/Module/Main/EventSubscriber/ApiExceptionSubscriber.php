<?php

declare(strict_types=1);

namespace App\Module\Main\EventSubscriber;

use App\Module\Task\Exception\TaskNotFoundException;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $throwable = $event->getThrowable();
        $statusCode = $this->resolveStatusCode($throwable);

        if (null === $statusCode) {
            return;
        }

        $event->setResponse($this->jsonError($throwable->getMessage(), $statusCode));
    }

    private function resolveStatusCode(Throwable $throwable): ?int
    {
        return match (true) {
            $throwable instanceof TaskNotFoundException => Response::HTTP_NOT_FOUND,
            $throwable instanceof AccessDeniedException => Response::HTTP_FORBIDDEN,
            $throwable instanceof AuthenticationException => Response::HTTP_UNAUTHORIZED,
            $throwable instanceof TooManyRequestsHttpException => Response::HTTP_TOO_MANY_REQUESTS,
            $throwable instanceof InvalidArgumentException,
            $throwable instanceof LogicException => Response::HTTP_BAD_REQUEST,
            default => null,
        };
    }

    private function jsonError(string $message, int $statusCode): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'message' => $message,
                'code' => $statusCode,
            ],
        ], $statusCode);
    }
}
