<?php

declare(strict_types=1);

namespace App\Contract;

use App\Dto\TaskPromptNode;

interface FieldRendererInterface
{
    public function render(TaskPromptNode $node, string $indent): ?string;
}
