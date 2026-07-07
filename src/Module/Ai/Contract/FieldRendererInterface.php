<?php

declare(strict_types=1);

namespace App\Module\Ai\Contract;

use App\Module\Task\Dto\TaskPromptNode;

interface FieldRendererInterface
{
    public function render(TaskPromptNode $node, string $indent): ?string;
}
