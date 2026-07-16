<?php

declare(strict_types=1);

namespace App\Module\Main\Security;

final readonly class SecureTokenGenerator
{
    public function generateRawToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
