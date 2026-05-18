<?php

declare(strict_types=1);

namespace App\Prompt\FieldRenderer;

use App\Contract\FieldRendererInterface;
use App\Dto\TaskPromptNode;

class DescriptionRenderer implements FieldRendererInterface
{
    public function render(TaskPromptNode $node, string $indent): ?string
    {
        if ($node->task->getDescription() === null) {
            return null;
        }

        return sprintf('%s  Описание: %s', $indent, $node->task->getDescription());
    }

}
