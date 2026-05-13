<?php

declare(strict_types=1);

namespace App\Builder;

class AnalyzeTaskPromptBuilder
{
    private string $title = '';
    private string $description = '';
    public function title(string $title): self
    {
        $this->title = trim($title);

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = trim((string) $description);

        return $this;
    }

    public function build(): string
    {
        $prompt = "Задача: {$this->title}\n";

        if ($this->description !== '') {
            $prompt .= "Описание: {$this->description}\n";
        }

        $prompt .= "\nДай краткий структурированный совет:\n"
            . "1. Как лучше выполнить эту задачу\n"
            . "2. На что обратить внимание\n"
            . '3. Предложи 2-3 подзадачи';

        return $prompt;
    }

}
