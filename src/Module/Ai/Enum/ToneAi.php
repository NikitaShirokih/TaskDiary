<?php

declare(strict_types=1);

namespace App\Module\Ai\Enum;

enum ToneAi: string
{
    case Neutral = 'neutral';
    case Angry = 'angry';
    case Happy = 'happy';
    case Friendly = 'friendly';

    public function instruction(): string
    {
        return match ($this) {
            self::Neutral => 'Перепиши описание в нейтральном, чётком и профессиональном стиле.',
            self::Angry => 'Перепиши описание в требовательном, жёстком и срочном стиле.',
            self::Happy => 'Перепиши описание в бодром, позитивном и энергичном стиле.',
            self::Friendly => 'Перепиши описание в дружелюбном, тёплом и понятном стиле.',
        };
    }

    public static function fromMixed(mixed $tone): self
    {
        if (!is_string($tone)) {
            return self::Neutral;
        }

        return self::tryFrom($tone) ?? self::Neutral;
    }
}
