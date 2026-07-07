<?php

declare(strict_types=1);

namespace App\Module\Ai\Builder;

use App\Module\Ai\Enum\ToneAi;

class PromptBuilder
{
    private string $title = '';
    private string $description = '';
    private ToneAi $tone = ToneAi::Neutral;

    public function title(string $title): self
    {
        $this->title = trim($title);

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = trim($description);

        return $this;
    }

    public function tone(ToneAi $tone): self
    {
        $this->tone = $tone;

        return $this;
    }

    public function build(): string
    {
        return implode("\n", [
            $this->tone->instruction(),
            'Улучши только описание задачи. Название используй только как контекст.',
            "Название задачи: {$this->title}",
            "Текущее описание: {$this->description}",
            'Верни только улучшенное описание.',
            'Не повторяй название задачи.',
            'Сохрани смысл оригинального описания.',
            'Ограничь ответ 3-4 предложениями.',
        ]);
    }
}
