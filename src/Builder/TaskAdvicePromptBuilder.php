<?php

declare(strict_types=1);

namespace App\Builder;

use App\Contract\PromptBuilderInterface;
use App\Dto\TaskPromptData;
use App\Prompt\TaskRelationTreeRenderer;

final readonly class TaskAdvicePromptBuilder implements PromptBuilderInterface
{
    public function __construct(
        private TaskRelationTreeRenderer $relationTreeRenderer,
    ) {
    }

    public function build(TaskPromptData $data): string
    {
        $taskContext = $this->relationTreeRenderer->render($data->parent);

        return sprintf(<<<PROMPT
## Контекст задачи

{$taskContext}

## Инструкция

{$data->instruction}
PROMPT);
    }
}
