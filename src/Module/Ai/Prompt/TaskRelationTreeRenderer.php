<?php

declare(strict_types=1);

namespace App\Module\Ai\Prompt;

use App\Module\Ai\Contract\FieldRendererInterface;
use App\Module\Task\Dto\TaskPromptNode;

final readonly class TaskRelationTreeRenderer
{
    /**
     * @param iterable<FieldRendererInterface> $fieldRenderers
     */
    public function __construct(
        private iterable $fieldRenderers,
    ) {
    }

    public function render(TaskPromptNode $node): string
    {
        return trim($this->renderNode($node, 0));
    }

    private function renderNode(TaskPromptNode $node, int $level): string
    {
        $indent = str_repeat('  ', $level);

        $lines = [];

        $lines[] = sprintf('%s- [%s] %s', $indent, $node->relationType->label(), $node->task->getTitle());

        foreach ($this->renderDetails($node, $indent) as $detailLine) {
            $lines[] = $detailLine;
        }

        foreach ($node->children as $child) {
            $lines[] = $this->renderNode($child, $level + 1);
        }

        if ($node->hiddenRelationsCount > 0) {
            $lines[] = sprintf(
                '%s  ...ещё скрыто связанных задач: %d',
                $indent,
                $node->hiddenRelationsCount,
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private function renderDetails(TaskPromptNode $node, string $indent): array
    {
        $lines = [];

        foreach ($this->fieldRenderers as $renderer) {
            $line = $renderer->render($node, $indent);
            if ($line !== null) {
                $lines[] = $line;
            }
        }

        return $lines;
    }
}
