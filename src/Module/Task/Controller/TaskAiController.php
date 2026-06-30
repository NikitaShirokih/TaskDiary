<?php

declare(strict_types=1);

namespace App\Module\Task\Controller;

use App\Entity\Task;
use App\Module\Task\Enum\TaskRights;
use App\Module\Ai\Enum\ToneAi;
use App\Module\User\Enum\UserRole;
use App\Module\Ai\Service\AiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[IsGranted(UserRole::USER->value)]
#[Route('/task', name: 'task_')]
final class TaskAiController extends AbstractController
{
    #[Route('/{id<\d+>}/ai-analyze', name: 'ai_analyze', methods: ['GET', 'POST'])]
    public function aiAnalyze(Task $task, AiService $aiService): JsonResponse
    {
        $this->denyAccessUnlessGranted(TaskRights::VIEW->value, $task);

        try {
            $result = $aiService->analyzeTask($task);

            return new JsonResponse(['result' => $result]);
        } catch (Throwable $e) {
            return new JsonResponse(
                ['error' => 'Ошибка: '.$e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/ai-improve-description', name: 'ai_improve_description', methods: ['POST'])]
    public function aiImproveDescription(Request $request, AiService $aiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Некорректный JSON.'], Response::HTTP_BAD_REQUEST);
        }

        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $tone = ToneAi::fromMixed($data['tone'] ?? null);

        if ('' === $description) {
            return new JsonResponse(['error' => 'Описание не может быть пустым.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $aiService->improveDescription($title, $description, $tone);

            return new JsonResponse(['result' => $result]);
        } catch (Throwable $e) {
            return new JsonResponse(
                ['error' => 'Ошибка GigaChat: '.$e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
