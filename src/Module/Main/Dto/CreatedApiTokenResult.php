<?php

declare(strict_types=1);

namespace App\Module\Main\Dto;

use App\Module\Main\Entity\ApiToken;

final readonly class CreatedApiTokenResult
{
    public function __construct(
        public ApiToken $apiToken,
        public string $plainToken,
    ) {
    }
}
