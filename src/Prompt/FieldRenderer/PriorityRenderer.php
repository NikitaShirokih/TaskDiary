<?php

declare(strict_types=1);

namespace App\Prompt\FieldRenderer;

use App\Contract\FieldRendererInterface;
use App\Dto\TaskPromptNode;

class PriorityRenderer implements FieldRendererInterface
{
    public function render(TaskPromptNode $node, string $indent): ?string
    {
        return sprintf('%s  Приоритет: %s', $indent, $node->task->getPriority()->value);
    }
}
