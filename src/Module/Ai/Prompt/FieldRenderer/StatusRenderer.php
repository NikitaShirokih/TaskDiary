<?php

declare(strict_types=1);

namespace App\Module\Ai\Prompt\FieldRenderer;

use App\Module\Ai\Contract\FieldRendererInterface;
use App\Module\Task\Dto\TaskPromptNode;

class StatusRenderer implements FieldRendererInterface
{
    public function render(TaskPromptNode $node, string $indent): string
    {
        return sprintf('%s  Статус: %s', $indent, $node->task->getStatus()->value);
    }
}
