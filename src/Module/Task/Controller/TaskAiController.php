<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Module\Task\Entity\Task;
use App\Module\Task\Enum\TaskRights;
use App\Module\Main\Enum\UserRole;
use App\Module\Ai\Service\AiImproveDescriptionRequestHandler;
use App\Module\Ai\Service\AiService;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[IsGranted(UserRole::USER->value)]
#[Route('/task', name: 'task_')]
final class TaskAiController extends AbstractController
{
    public function __construct(
        private readonly AiService $aiService,
        private readonly AiImproveDescriptionRequestHandler $aiImproveDescriptionRequestHandler,
    ) {
    }

    #[Route('/{id<\d+>}/ai-analyze', name: 'ai_analyze', methods: ['GET', 'POST'])]
    public function aiAnalyze(Task $task): JsonResponse
    {
        $this->denyAccessUnlessGranted(TaskRights::VIEW->value, $task);

        try {
            $result = $this->aiService->analyzeTask($task);

            return new JsonResponse(['result' => $result]);
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|RuntimeException|ServerExceptionInterface|TransportExceptionInterface $e) {
            return new JsonResponse(
                ['error' => 'Ошибка: '.$e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/ai-improve-description', name: 'ai_improve_description', methods: ['POST'])]
    public function aiImproveDescription(Request $request): JsonResponse
    {
        try {
            $data = $this->aiImproveDescriptionRequestHandler->handle($request);

            $result = $this->aiService->improveDescription($data->title, $data->description, $data->tone);

            return new JsonResponse(['result' => $result]);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|RuntimeException|ServerExceptionInterface|TransportExceptionInterface $e) {
            return new JsonResponse(
                ['error' => 'Ошибка GigaChat: '.$e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
