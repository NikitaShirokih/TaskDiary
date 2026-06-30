<?php

declare(strict_types=1);

namespace App\Module\Ai\Prompt\FieldRenderer;

use App\Module\Ai\Contract\FieldRendererInterface;
use App\Module\Task\Dto\TaskPromptNode;

class StartTimeRenderer implements FieldRendererInterface
{
    public function render(TaskPromptNode $node, string $indent): ?string
    {
        if ($node->task->getStartTime() === null) {
            return null;
        }

        return sprintf('%s  Начало: %s', $indent, $node->task->getStartTime()->format('Y-m-d H:i:s'),
        );
    }
}
