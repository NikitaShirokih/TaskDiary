<?php

declare(strict_types=1);

namespace App\Module\Ai\Prompt\FieldRenderer;

use App\Module\Ai\Contract\FieldRendererInterface;
use App\Module\Task\Dto\TaskPromptNode;

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
