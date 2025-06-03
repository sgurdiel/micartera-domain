<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Infrastructure\Doctrine\Transaction;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting\MovementRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\TransactionPersistence;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(TransactionPersistence::class)]
class TransactionPersistenceTest extends TestCase
{
    public function testGetRepositoryReturnsStockRepository(): void
    {
        $repo = $this->createStub(AcquisitionRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->with(Acquisition::class)->willReturn($repo);

        $persistence = new TransactionPersistence($registry);
        $result = $persistence->getRepository();
        $this->assertInstanceOf(EntityRepositoryInterface::class, $result);
        $this->assertInstanceOf(AcquisitionRepositoryInterface::class, $result);
    }

    public function testGetRepositoryThrowsOnInvalidRepository(): void
    {
        $repo = $this->createStub(EntityRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->with(Acquisition::class)->willReturn($repo);

        $persistence = new TransactionPersistence($registry);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('entityConfigurationContainsInvalidRepository');
        $persistence->getRepository();
    }

    public function testGetRepositoryForLiquidationReturnsCurrencyRepository(): void
    {
        $repo = $this->createStub(LiquidationRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->with(Liquidation::class)->willReturn($repo);

        $persistence = new TransactionPersistence($registry);
        $result = $persistence->getRepositoryForLiquidation();
        $this->assertInstanceOf(EntityRepositoryInterface::class, $result);
        $this->assertInstanceOf(LiquidationRepositoryInterface::class, $result);
    }

    public function testGetRepositoryForliquidationThrowsOnInvalidRepository(): void
    {
        $repo = $this->createStub(EntityRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->with(Liquidation::class)->willReturn($repo);

        $persistence = new TransactionPersistence($registry);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('entityConfigurationContainsInvalidRepository');
        $persistence->getRepositoryForLiquidation();
    }

    public function testGetRepositoryForMovementReturnsCurrencyRepository(): void
    {
        $repo = $this->createStub(MovementRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->with(Movement::class)->willReturn($repo);

        $persistence = new TransactionPersistence($registry);
        $result = $persistence->getRepositoryForMovement();
        $this->assertInstanceOf(EntityRepositoryInterface::class, $result);
        $this->assertInstanceOf(MovementRepositoryInterface::class, $result);
    }

    public function testGetRepositoryForMovementThrowsOnInvalidRepository(): void
    {
        $repo = $this->createStub(EntityRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->with(Movement::class)->willReturn($repo);

        $persistence = new TransactionPersistence($registry);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('entityConfigurationContainsInvalidRepository');
        $persistence->getRepositoryForMovement();
    }
}
