<?php

declare(strict_types=1);

namespace App\Contract;

use App\Dto\TaskPromptData;

interface PromptBuilderInterface
{
    public function build(TaskPromptData $data): string;
}
