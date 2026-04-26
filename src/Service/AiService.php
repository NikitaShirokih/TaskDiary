<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
    ) {
    }

    private function getAccessToken(): string
    {
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

        $data = $response->toArray();

        if (!isset($data['access_token'])) {
            throw new \RuntimeException('GigaChat: не удалось получить токен. Ответ: '.json_encode($data));
        }

        return $data['access_token'];
    }

    public function analyzeTask(string $title, ?string $description): string
    {
        $token = $this->getAccessToken();

        $userText = "Задача: {$title}\n";

        if ($description) {
            $userText .= "Описание: {$description}\n";
        }

        $userText .= "\nДай краткий структурированный совет:\n"
            ."1. Как лучше выполнить эту задачу\n"
            ."2. На что обратить внимание\n"
            .'3. Предложи 2-3 подзадачи';

        $response = $this->httpClient->request(
            'POST',
            'https://gigachat.devices.sberbank.ru/api/v1/chat/completions',
            [
                'verify_peer' => false,
                'verify_host' => false,
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'GigaChat',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Ты умный помощник по управлению задачами. Отвечай на русском языке, кратко, структурированно и по делу. Не используй markdown-разметку.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $userText,
                        ],
                    ],
                    'temperature' => 0.6,
                    'max_tokens' => 500,
                ],
            ]
        );

        $data = $response->toArray();

        return $data['choices'][0]['message']['content'] ?? 'Нет ответа от AI';
    }

    public function improveDescription(string $title, string $description, string $tone = 'neutral'): string
    {
        $token = $this->getAccessToken();

        $toneInstruction = match ($tone) {
            'friendly' => 'Перепиши описание в дружелюбном, тёплом и позитивном стиле.',
            'angry' => 'Перепиши описание в требовательном, жёстком и срочном стиле.',
            default => 'Перепиши описание в нейтральном, чётком и профессиональном стиле.',
        };

        $prompt = "{$toneInstruction} Улучши ТОЛЬКО описание задачи. Не включай название в ответ.\n"
            ."Название задачи (только для контекста): {$title}\n"
            ."Текущее описание: {$description}\n\n"
            ."Требования:\n"
            ."- Верни ТОЛЬКО улучшенный текст описания\n"
            ."- Не повторяй название задачи в ответе\n"
            ."- Не добавляй заголовки, подписи и пояснения\n"
            ."- Сохрани смысл оригинала\n"
            ."- Без markdown-разметки\n"
            .'- Максимум 3-4 предложения';

        $response = $this->httpClient->request(
            'POST',
            'https://gigachat.devices.sberbank.ru/api/v1/chat/completions',
            [
                'verify_peer' => false,
                'verify_host' => false,
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ],
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

        $data = $response->toArray();

        return $data['choices'][0]['message']['content'] ?? 'Нет ответа от AI';
    }

    // ── Вспомогательный метод: UUID v4 ────────────────────────────────────
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0x0FFF) | 0x4000,
            mt_rand(0, 0x3FFF) | 0x8000,
            mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF)
        );
    }
}
