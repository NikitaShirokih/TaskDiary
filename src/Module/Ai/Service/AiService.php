<?php

declare(strict_types=1);

namespace App\Module\Ai\Service;

use App\Module\Ai\Builder\PromptBuilder;
use App\Module\Ai\Contract\PromptBuilderInterface;
use App\Entity\Task;
use App\Module\Ai\Enum\ToneAi;
use App\Module\Ai\Prompt\TaskPromptContextFactory;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;


final class AiService
{
    private ?string $accessToken = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly PromptBuilderInterface $promptBuilder,
        private readonly TaskPromptContextFactory $taskPromptContextFactory,
    ) {
    }

    public function analyzeTask(Task $task): string
    {
        $promptData = $this->taskPromptContextFactory->create($task);

        $prompt = $this->promptBuilder->build($promptData);

        return $this->askGigaChat(
            systemMessage: 'Ты умный помощник по управлению задачами. Отвечай на русском языке, кратко, структурированно и по делу.',
            userPrompt: $prompt,
            temperature: 0.6,
            maxTokens: 500,
        );
    }

    public function improveDescription(
        string $title,
        string $description,
        ToneAi $tone = ToneAi::Neutral,
    ): string {
        $title = trim($title);
        $description = trim($description);

        if ('' === $title) {
            throw new \InvalidArgumentException('Название задачи не может быть пустым');
        }

        if ('' === $description) {
            throw new \InvalidArgumentException('Описание задачи не может быть пустым');
        }

        $prompt = (new PromptBuilder())
            ->title($title)
            ->description($description)
            ->tone($tone)
            ->build();

        return $this->askGigaChat(
            systemMessage: 'Ты помощник по улучшению текста задач. Ты возвращаешь ТОЛЬКО улучшенный текст описания — без названия, без заголовков, без пояснений.',
            userPrompt: $prompt,
            temperature: 0.7,
            maxTokens: 300,
        );
    }

    private function askGigaChat(
        string $systemMessage,
        string $userPrompt,
        float $temperature,
        int $maxTokens,
    ): string {
        $response = $this->httpClient->request(
            'POST',
            'https://gigachat.devices.sberbank.ru/api/v1/chat/completions',
            [
                'verify_peer' => false,
                'verify_host' => false,
                'timeout' => 30,
                'headers' => $this->getAuthHeaders(),
                'json' => [
                    'model' => 'GigaChat',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemMessage,
                        ],
                        [
                            'role' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                ],
            ]
        );

        $content = $response->toArray()['choices'][0]['message']['content'] ?? null;

        if (!is_string($content) || '' === trim($content)) {
            throw new RuntimeException('AI не вернул содержательный ответ');
        }

        return trim($content);
    }

    private function getAccessToken(): string
    {
        if (null !== $this->accessToken) {
            return $this->accessToken;
        }

        $response = $this->httpClient->request(
            'POST',
            'https://ngw.devices.sberbank.ru:9443/api/v2/oauth',
            [
                'verify_peer' => false,
                'verify_host' => false,
                'headers' => [
                    'Authorization' => 'Basic '.$this->apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'RqUID' => $this->generateUuid(),
                ],
                'body' => 'scope=GIGACHAT_API_PERS',
            ]
        );

        $accessToken = $response->toArray()['access_token'] ?? null;

        if (!is_string($accessToken) || '' === trim($accessToken)) {
            throw new RuntimeException('GigaChat: не удалось получить токен.');
        }

        return $this->accessToken = $accessToken;
    }

    /**
     * @return array<string, string>
     */
    private function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->getAccessToken(),
            'Content-Type' => 'application/json',
        ];
    }

    private function generateUuid(): string
    {
        return Uuid::v4()->toRfc4122();
    }
}
