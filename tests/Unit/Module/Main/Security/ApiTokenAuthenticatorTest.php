<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Main\Security;

use App\Module\Main\Entity\ApiToken;
use App\Module\Main\Entity\User;
use App\Module\Main\Repository\ApiTokenRepository;
use App\Module\Main\Security\ApiTokenAuthenticator;
use App\Module\Main\Security\SecureTokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class ApiTokenAuthenticatorTest extends TestCase
{
    public function testSupportsApiPath(): void
    {
        self::assertTrue($this->authenticator()->supports(Request::create('/api/tasks')));
    }

    #[DataProvider('webPaths')]
    public function testDoesNotSupportWebPaths(string $path): void
    {
        self::assertFalse($this->authenticator()->supports(Request::create($path)));
    }

    /** @return iterable<int, array{string}> */
    public static function webPaths(): iterable
    {
        yield ['/login']; yield ['/task']; yield ['/profile/api-tokens'];
    }

    public function testMissingAuthorizationHeaderFails(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->authenticator()->authenticate(Request::create('/api/tasks'));
    }

    public function testInvalidAuthorizationFormatFails(): void
    {
        $request = Request::create('/api/tasks');
        $request->headers->set('Authorization', 'Token abc');
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->authenticator()->authenticate($request);
    }

    public function testValidBearerTokenLoadsUserAndMarksTokenUsed(): void
    {
        $user = new User();
        $apiToken = new ApiToken($user, 'CLI', hash('sha256', 'plain-token'));
        $inner = $this->createMock(EntityPersister::class);
        $inner->expects($this->once())->method('load')->with(['tokenHash' => hash('sha256', 'plain-token'), 'revokedAt' => null])->willReturn($apiToken);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');
        $request = Request::create('/api/tasks');
        $request->headers->set('Authorization', 'Bearer plain-token');

        $passport = $this->authenticator($this->repository($inner), $entityManager)->authenticate($request);
        $badge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $badge);
        self::assertSame($user, ($badge->getUserLoader())($badge->getUserIdentifier()));
        self::assertNotNull($apiToken->getLastUsedAt());
    }

    public function testUnknownBearerTokenFailsAuthentication(): void
    {
        $inner = $this->createMock(EntityPersister::class);
        $inner->expects($this->once())
            ->method('load')
            ->with(['tokenHash' => hash('sha256', 'unknown-token'), 'revokedAt' => null])
            ->willReturn(null);
        $request = Request::create('/api/tasks');
        $request->headers->set('Authorization', 'Bearer unknown-token');
        $passport = $this->authenticator($this->repository($inner))->authenticate($request);
        $badge = $passport->getBadge(UserBadge::class);

        self::assertInstanceOf(UserBadge::class, $badge);
        $this->expectException(CustomUserMessageAuthenticationException::class);

        ($badge->getUserLoader())($badge->getUserIdentifier());
    }

    public function testAuthenticationFailureReturnsStandardJson401(): void
    {
        $response = $this->authenticator()->onAuthenticationFailure(Request::create('/api'), new AuthenticationException('Bad token'));
        self::assertSame(401, $response->getStatusCode());
        self::assertSame(['error' => ['message' => 'Bad token', 'code' => 401]], json_decode((string) $response->getContent(), true));
    }

    public function testStartReturnsStandardJson401(): void
    {
        $response = $this->authenticator()->start(Request::create('/api'));
        $payload = json_decode((string) $response->getContent(), true);
        self::assertSame(401, $response->getStatusCode());
        self::assertNotEmpty($payload['error']['message']);
        self::assertSame(401, $payload['error']['code']);
    }

    private function authenticator(?ApiTokenRepository $repository = null, ?EntityManagerInterface $entityManager = null): ApiTokenAuthenticator
    {
        return new ApiTokenAuthenticator(
            $repository ?? $this->repository(),
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            new SecureTokenGenerator(),
        );
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
