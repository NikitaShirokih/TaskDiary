<?php

declare(strict_types=1);

namespace App\Prompt;

use App\Dto\TaskPromptNode;

final readonly class TaskRelationTreeRenderer
{
    public function render(TaskPromptNode $node): string
    {
        return trim($this->renderNode($node, 0));
    }

    private function renderNode(TaskPromptNode $node, int $level): string
    {
        $indent = str_repeat('  ', $level);

        $lines = [];

        $lines[] = sprintf(
            '%s- [%s] %s',
            $indent,
            $node->relationType->label(),
            $node->title,
        );

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

        if (null !== $node->description) {
            $lines[] = sprintf('%s  Описание: %s', $indent, $node->description);
        }

        $lines[] = sprintf('%s  Статус: %s', $indent, $node->status);
        $lines[] = sprintf('%s  Приоритет: %s', $indent, $node->priority);

        if (null !== $node->type) {
            $lines[] = sprintf('%s  Тип: %s', $indent, $node->type);
        }

        if (null !== $node->startTime) {
            $lines[] = sprintf(
                '%s  Начало: %s',
                $indent,
                $node->startTime->format('Y-m-d H:i'),
            );
        }

        if (null !== $node->endTime) {
            $lines[] = sprintf(
                '%s  Окончание: %s',
                $indent,
                $node->endTime->format('Y-m-d H:i'),
            );
        }

        return $lines;
    }
}
