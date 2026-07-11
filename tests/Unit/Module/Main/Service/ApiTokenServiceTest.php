<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Main\Service;

use App\Module\Main\Dto\CreatedApiTokenResult;
use App\Module\Main\Entity\ApiToken;
use App\Module\Main\Entity\User;
use App\Module\Main\Repository\ApiTokenRepository;
use App\Module\Main\Service\ApiTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ApiTokenServiceTest extends TestCase
{
    public function testCreateTokenPersistsHashAndReturnsPlainToken(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $persisted = null;
        $entityManager->expects($this->once())->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void { $persisted = $entity; });
        $entityManager->expects($this->once())->method('flush');

        $result = $this->service(entityManager: $entityManager)->createToken(new User(), 'CLI');

        self::assertInstanceOf(CreatedApiTokenResult::class, $result);
        self::assertStringStartsWith('td_', $result->plainToken);
        self::assertSame($result->apiToken, $persisted);
        self::assertNotSame($result->plainToken, $result->apiToken->getTokenHash());
        self::assertSame(hash('sha256', $result->plainToken), $result->apiToken->getTokenHash());
    }

    public function testCreateTokenRejectsEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service()->createToken(new User(), '  ');
    }

    public function testGetActiveTokensReturnsRepositoryResult(): void
    {
        $user = new User();
        $token = new ApiToken($user, 'CLI', 'hash');
        $inner = $this->createMock(EntityPersister::class);
        $inner->expects($this->once())->method('loadAll')->with(['user' => $user, 'revokedAt' => null], ['createdAt' => 'DESC'], null, null)->willReturn([$token]);

        self::assertSame([$token], $this->service($this->repository($inner))->getActiveTokens($user));
    }

    public function testRevokeTokenMarksTokenAndFlushes(): void
    {
        $user = new User();
        $token = new ApiToken($user, 'CLI', 'hash');
        $inner = $this->createMock(EntityPersister::class);
        $inner->expects($this->once())->method('load')->with(['id' => 42, 'user' => $user, 'revokedAt' => null])->willReturn($token);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $this->service($this->repository($inner), $entityManager)->revokeToken($user, 42);

        self::assertNotNull($token->getRevokedAt());
    }

    public function testRevokeUnknownTokenIsRejected(): void
    {
        $inner = $this->createStub(EntityPersister::class);
        $inner->method('load')->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->service($this->repository($inner))->revokeToken(new User(), 999);
    }

    private function service(?ApiTokenRepository $repository = null, ?EntityManagerInterface $entityManager = null): ApiTokenService
    {
        return new ApiTokenService($repository ?? $this->repository(), $entityManager ?? $this->createStub(EntityManagerInterface::class));
    }

    private function repository(?EntityPersister $inner = null): ApiTokenRepository
    {
        $inner ??= $this->createStub(EntityPersister::class);
        $unitOfWork = $this->createStub(UnitOfWork::class);
        $unitOfWork->method('getEntityPersister')->willReturn($inner);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getClassMetadata')->willReturn(new ClassMetadata(ApiToken::class));
        $manager->method('getUnitOfWork')->willReturn($unitOfWork);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);
        return new ApiTokenRepository($registry);
    }
}
