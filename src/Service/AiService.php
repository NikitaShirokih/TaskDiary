<?php

namespace App\Service;

use App\Enum\ToneAi;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Builder\PromptBuilder;
use App\Builder\AnalyzeTaskPromptBuilder;
use Symfony\Component\Uid\Uuid;


class AiService
{
    private ?string $accessToken = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string              $apiKey,
    ) {
    }


    private function getAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $response = $this->httpClient->request(
            'POST',
            'https://ngw.devices.sberbank.ru:9443/api/v2/oauth',
            [
                'verify_peer' => false,
                'verify_host' => false,
                'headers' => [
                    'Authorization' => 'Basic ' . $this->apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'RqUID' => $this->generateUuid(),
                ],
                'body' => 'scope=GIGACHAT_API_PERS',
            ]
        );

        return $this->accessToken = $response->toArray()['access_token']
            ?? throw new \RuntimeException('GigaChat: не удалось получить токен.');
    }

    private function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Content-Type' => 'application/json',
        ];
    }


    public function analyzeTask(string $title, ?string $description): string
    {
        $title = trim($title);

        if ('' === $title) {
            throw new \InvalidArgumentException('Название задачи не может быть пустым');
        }

        $prompt = (new AnalyzeTaskPromptBuilder())
            ->title($title)
            ->description($description)
            ->build();

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
                            'content' => 'Ты умный помощник по управлению задачами. Отвечай на русском языке, кратко, структурированно и по делу. Не используй markdown-разметку.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.6,
                    'max_tokens' => 500,
                ],
            ]
        );

        $content = $response->toArray()['choices'][0]['message']['content'] ?? null;

        if (!is_string($content) || '' === trim($content)) {
            throw new \RuntimeException('AI не вернул содержательный ответ');
        }

        return trim($content);
    }


    public function improveDescription(string $title, string $description, ToneAi $tone = ToneAi::Neutral): string
    {
        $prompt = (new PromptBuilder())
            ->title($title)
            ->description($description)
            ->tone($tone)
            ->build();

        $response = $this->httpClient->request(
            'POST',
            'https://gigachat.devices.sberbank.ru/api/v1/chat/completions',
            [
                'verify_peer' => false,
                'verify_host' => false,
                'headers' => $this->getAuthHeaders(),
                'json' => [
                    'model' => 'GigaChat',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Ты помощник по улучшению текста задач. Ты возвращаешь ТОЛЬКО улучшенный текст описания — без названия, без заголовков, без пояснений.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 300,
                ],
            ]
        );

        $content = $response->toArray()['choices'][0]['message']['content'] ?? 'Нет ответа от AI';
        return trim($content);
    }

    // ── Вспомогательный метод: UUID v4 ────────────────────────────────────
    private function generateUuid(): string
    {
        return Uuid::v4()->toRfc4122();
    }
}
