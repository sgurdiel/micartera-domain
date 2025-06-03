<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Transaction\Accounting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(Movement::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(TransactionAmountVO::class)]
#[UsesClass(TransactionAmountActionableVO::class)]
#[UsesClass(TransactionExpenseVO::class)]
#[UsesClass(MovementPriceVO::class)]
class MovementTest extends TestCase
{
    private Stock&Stub $stock;
    private MockObject&MovementRepositoryInterface $repoMovement;
    private AcquisitionRepositoryInterface&MockObject $repoAcquisition;
    private LiquidationRepositoryInterface&MockObject $repoLiquidation;
    private Stub&TransactionPersistenceInterface $transactionPersistence;

    public function setUp(): void
    {
        $this->repoMovement = $this->createMock(MovementRepositoryInterface::class);
        $this->repoAcquisition = $this->createMock(AcquisitionRepositoryInterface::class);
        $this->repoLiquidation = $this->createMock(LiquidationRepositoryInterface::class);
        $this->transactionPersistence = $this->createStub(TransactionPersistenceInterface::class);
        $this->transactionPersistence->method('getRepository')->willReturn($this->repoAcquisition);
        $this->transactionPersistence->method('getRepositoryForMovement')->willReturn($this->repoMovement);
        $this->transactionPersistence->method('getRepositoryForLiquidation')->willReturn($this->repoLiquidation);
        // @var Stock&Stub
        $this->stock = $this->createStub(Stock::class);
        $this->stock->method('sameId')->willReturn(true);
    }

    #[DataProvider('createValues2')]
    public function testIsCreated2(
        string $acquisitionAmountActionable,
        string $acquisitionPrice,
        string $acquisitionExpenses,
        string $liquidationAmountActionable,
        string $liquidationPrice,
        string $liquidationExpenses,
        string $movementAmount,
        string $movementAcquisitionPrice,
        string $movementAcquisitionExpenses,
        string $movementLiquidationPrice,
        string $movementLiquidationExpenses,
    ): void {
        $this->repoMovement->expects($this->once())->method('persist');

        /** @var Currency&MockObject */
        $currency = $this->createStub(Currency::class);
        $currency->method('getDecimals')->willReturn(2);

        /** @var Acquisition&Stub */
        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($this->stock);
        $acquisition->method('getDateTimeUtc')->willReturn(new \DateTime('30 minutes ago'));
        $acquisition->method('sameId')->willReturn(true);
        $acquisition->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO($acquisitionAmountActionable));
        $acquisition->method('getPrice')->willReturn(new StockPriceVO($acquisitionPrice, $currency));
        $acquisition->method('getCurrency')->willReturn($currency);
        $acquisition->method('getExpensesUnaccountedFor')->willReturn(new TransactionExpenseVO($acquisitionExpenses, $currency));

        /** @var Liquidation&Stub */
        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);
        $liquidation->method('getDateTimeUtc')->willReturn(new \DateTime('20 minutes ago'));
        $liquidation->method('sameId')->willReturn(true);
        $liquidation->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO($liquidationAmountActionable));
        $liquidation->method('getPrice')->willReturn(new StockPriceVO($liquidationPrice, $currency));
        $liquidation->method('getCurrency')->willReturn($currency);
        $liquidation->method('getExpensesUnaccountedFor')->willReturn(new TransactionExpenseVO($liquidationExpenses, $currency));

        $accountingMovement = new Movement($this->transactionPersistence, $acquisition, $liquidation);
        $this->assertSame($acquisition, $accountingMovement->getAcquisition());
        $this->assertSame($liquidation, $accountingMovement->getLiquidation());
        $this->assertTrue($accountingMovement->sameId($accountingMovement));
        $this->assertSame(new TransactionAmountVO($movementAmount)->getValue(), $accountingMovement->getAmount()->getValue());
        $this->assertSame($movementAcquisitionPrice, $accountingMovement->getAcquisitionPrice()->getValueFormatted());
        $this->assertSame($movementLiquidationPrice, $accountingMovement->getLiquidationPrice()->getValueFormatted());
        $this->assertSame($movementAcquisitionExpenses, $accountingMovement->getAcquisitionExpenses()->getValue());
        $this->assertSame($movementLiquidationExpenses, $accountingMovement->getLiquidationExpenses()->getValue());
    }

    public static function createValues2(): array
    {
        return [
            ['10', '57.8000', '23.54', '10', '60.8000', '15.66', '10', '578.0000', '23.54', '608.0000', '15.66'],
            ['100', '578.0000', '23.54', '100', '608.0000', '15.66', '100', '57800.0000', '23.54', '60800.0000', '15.66'],
            ['200', '1.1234', '10.55', '100', '1.5678', '5.45', '100', '112.3400', '5.27', '156.7800', '5.45'],
            ['95', '61.6634', '10.55', '95', '61.6634', '10.55', '95', '5858.0300', '10.55', '5858.0300', '10.55'],
            ['10', '5.7800', '4.93', '100', '8.5300', '3.51', '10', '57.8000', '4.93', '85.3000', '0.35'],
        ];
    }

    public function testMovementWithTransactionsHavingDifferentStockThrowsException(): void
    {
        /** @var Stock&Stub */
        $stock = $this->createStub(Stock::class);
        $stock->method('sameId')->willReturn(false);

        /** @var Acquisition&Stub */
        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($stock);

        /** @var Liquidation&Stub */
        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($stock);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('transactionAssertStock');
        new Movement($this->transactionPersistence, $acquisition, $liquidation);
    }

    public function testLiquidationDateNotAfterAcquistionThrowsException(): void
    {
        $price = $this->createMock(StockPriceVO::class);

        /** @var Acquisition&Stub */
        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($this->stock);
        $acquisition->method('getDateTimeUtc')->willReturn(new \DateTime('20 minutes ago'));
        $acquisition->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO('3'));
        $acquisition->method('getPrice')->willReturn($price);

        /** @var Liquidation&Stub */
        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);
        $liquidation->method('getDateTimeUtc')->willReturn(new \DateTime('30 minutes ago'));
        $liquidation->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO('3'));
        $liquidation->method('getPrice')->willReturn($price);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('accountingMovementAssertDateTime');
        new Movement($this->transactionPersistence, $acquisition, $liquidation);
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        /** @var Movement */
        $accountingMovement = $this->getMockBuilder(Movement::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $entity = new class implements EntityInterface {
            public function sameId(EntityInterface $otherEntity): bool
            {
                return true;
            }
        };
        $this->expectException(\InvalidArgumentException::class);
        $accountingMovement->sameId($entity);
    }

    public function testAcquisitionWithoutAmountOutstandingThrowsException(): void
    {
        /** @var Acquisition&Stub */
        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($this->stock);
        $acquisition->method('getDateTimeUtc')->willReturn(new \DateTime('30 minutes ago'));
        $acquisition->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO('0'));

        /** @var Liquidation&Stub */
        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);
        $liquidation->method('getDateTimeUtc')->willReturn(new \DateTime('20 minutes ago'));
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('accountingMovementAcquisitionHasNoAmountOutstanding');
        new Movement($this->transactionPersistence, $acquisition, $liquidation);
    }

    public function testLiquidationWithoutAmountRemainingThrowsException(): void
    {
        /** @var Acquisition&Stub */
        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($this->stock);
        $acquisition->method('getDateTimeUtc')->willReturn(new \DateTime('30 minutes ago'));
        $acquisition->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO('10'));

        /** @var Liquidation&Stub */
        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);
        $liquidation->method('getDateTimeUtc')->willReturn(new \DateTime('20 minutes ago'));
        $liquidation->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO('0'));
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('accountingMovementLiquidationHasNoAmountRemaining');
        new Movement($this->transactionPersistence, $acquisition, $liquidation);
    }
}
