<?php

declare(strict_types=1);

namespace App\Module\Ai\Prompt\FieldRenderer;

use App\Module\Ai\Contract\FieldRendererInterface;
use App\Module\Task\Dto\TaskPromptNode;

class TypeRenderer implements FieldRendererInterface
{
    public function render(TaskPromptNode $node, string $indent): string
    {
        return sprintf('%s  Тип: %s', $indent, $node->task->getTitle());
    }
}
