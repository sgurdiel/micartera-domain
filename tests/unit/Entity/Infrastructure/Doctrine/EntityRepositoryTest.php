<?php

declare(strict_types=1);

namespace Tests\unit\Entity\Infrastructure\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

class DummyEntity implements EntityInterface
{
    public function sameId(EntityInterface $other): bool
    {
        // For testing, just return true or implement as needed
        return $other instanceof self;
    }
}

class DummyRepository extends EntityRepository
{
    // Expose protected methods for testing if needed
}

/**
 * @internal
 */
#[CoversClass(EntityRepository::class)]
class EntityRepositoryTest extends TestCase
{
    private ManagerRegistry&MockObject $managerRegistry;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
    }

    public function testPersistThrowsOnInvalidEntity()
    {
        $this->expectException(DomainViolationException::class);

        $repository = new DummyRepository($this->managerRegistry, DummyEntity::class);

        $invalidEntity = $this->createMock(EntityInterface::class);

        $repository->persist($invalidEntity);
    }
}
