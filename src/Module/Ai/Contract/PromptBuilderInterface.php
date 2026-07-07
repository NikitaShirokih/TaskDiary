<?php

declare(strict_types=1);

namespace App\Module\Ai\Contract;

use App\Module\Task\Dto\TaskPromptData;

interface PromptBuilderInterface
{
    public function build(TaskPromptData $data): string;
}
