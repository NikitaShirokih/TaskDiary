<?php

declare(strict_types=1);

namespace App\Module\Ai\Prompt\FieldRenderer;

use App\Module\Ai\Contract\FieldRendererInterface;
use App\Module\Task\Dto\TaskPromptNode;

class PriorityRenderer implements FieldRendererInterface
{
    public function render(TaskPromptNode $node, string $indent): ?string
    {
        return sprintf('%s  Приоритет: %s', $indent, $node->task->getPriority()->value);
    }
}
