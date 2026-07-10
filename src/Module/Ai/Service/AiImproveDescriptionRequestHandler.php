<?php

declare(strict_types=1);

namespace App\Module\Ai\Service;

use App\Module\Ai\Dto\AiImproveDescriptionData;
use App\Module\Ai\Enum\ToneAi;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

final readonly class AiImproveDescriptionRequestHandler
{
    public function handle(Request $request): AiImproveDescriptionData
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new InvalidArgumentException('Некорректный JSON.');
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));

        if ('' === $description) {
            throw new InvalidArgumentException('Описание не может быть пустым.');
        }

        return new AiImproveDescriptionData(
            title: $title,
            description: $description,
            tone: ToneAi::fromMixed($payload['tone'] ?? null),
        );
    }
}
