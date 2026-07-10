<?php

declare(strict_types=1);

namespace App\Module\Ai\Dto;

use App\Module\Ai\Enum\ToneAi;

final readonly class AiImproveDescriptionData
{
    public function __construct(
        public string $title,
        public string $description,
        public ToneAi $tone,
    ) {
    }
}
