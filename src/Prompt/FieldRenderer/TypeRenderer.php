<?php

declare(strict_types=1);

namespace App\Prompt\FieldRenderer;

use App\Contract\FieldRendererInterface;
use App\Dto\TaskPromptNode;

class TypeRenderer implements FieldRendererInterface
{
    public function render(TaskPromptNode $node, string $indent): ?string
    {
        if ($node->task->getTitle() === null) {
            return null;
        }
        return sprintf('%s  Тип: %s', $indent, $node->task->getTitle());
    }
}
