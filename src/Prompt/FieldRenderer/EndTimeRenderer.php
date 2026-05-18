<?php

declare(strict_types=1);

namespace App\Prompt\FieldRenderer;

use App\Contract\FieldRendererInterface;
use App\Dto\TaskPromptNode;

class EndTimeRenderer implements FieldRendererInterface
{
    public function render(TaskPromptNode $node, string $indent): ?string
    {
        if ($node->task->getEndTime() === null) {
            return null;
        }
        return sprintf('%s  Окончание: %s', $indent, $node->task->getEndTime()->format('Y-m-d H:i:s'));
    }
}
