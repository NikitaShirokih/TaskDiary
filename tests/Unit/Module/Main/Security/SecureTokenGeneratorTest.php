<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Main\Security;

use App\Module\Main\Security\SecureTokenGenerator;
use PHPUnit\Framework\TestCase;

final class SecureTokenGeneratorTest extends TestCase
{
    public function testGenerateRawTokenReturnsUniqueNonEmptyTokens(): void
    {
        $generator = new SecureTokenGenerator();
        $firstToken = $generator->generateRawToken();
        $secondToken = $generator->generateRawToken();

        self::assertNotSame('', $firstToken);
        self::assertSame(64, strlen($firstToken));
        self::assertNotSame($firstToken, $secondToken);
    }

    public function testHashTokenReturnsDeterministicSha256DifferentFromRawToken(): void
    {
        $generator = new SecureTokenGenerator();
        $rawToken = 'raw-token';
        $expectedHash = hash('sha256', $rawToken);

        self::assertSame($expectedHash, $generator->hashToken($rawToken));
        self::assertSame($expectedHash, $generator->hashToken($rawToken));
        self::assertNotSame($rawToken, $generator->hashToken($rawToken));
    }
}
