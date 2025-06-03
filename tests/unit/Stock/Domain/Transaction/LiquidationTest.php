<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Transaction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAbstract;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(Liquidation::class)]
#[CoversClass(TransactionAbstract::class)]
#[UsesClass(Currency::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Movement::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(Stock::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(AcquisitionCollection::class)]
#[UsesClass(TransactionAmountActionableVO::class)]
#[UsesClass(TransactionAmountVO::class)]
#[UsesClass(TransactionExpenseVO::class)]
class LiquidationTest extends TestCase
{
    private Currency&Stub $currency;
    private StockPriceVO $price;
    private Stock&Stub $stock;
    private static \DateTime $dateTimeUtc;
    private static TransactionAmountVO $amount;
    private TransactionExpenseVO $expenses;
    private Account&Stub $account;
    private MockObject&MovementRepositoryInterface $repoMovement;
    private AcquisitionRepositoryInterface&Stub $repoAcquisition;
    private LiquidationRepositoryInterface&MockObject $repoLiquidation;
    private Stub&TransactionPersistenceInterface $transactionPersistence;

    public static function setUpBeforeClass(): void
    {
        self::$dateTimeUtc = new \DateTime('yesterday', new \DateTimeZone('UTC'));
        self::$amount = new TransactionAmountVO('100');
    }

    public function setUp(): void
    {
        $this->repoMovement = $this->createMock(MovementRepositoryInterface::class);
        $this->repoAcquisition = $this->createMock(AcquisitionRepositoryInterface::class);
        $this->repoLiquidation = $this->createMock(LiquidationRepositoryInterface::class);
        $this->repoLiquidation->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        $this->transactionPersistence = $this->createStub(TransactionPersistenceInterface::class);
        $this->transactionPersistence->method('getRepository')->willReturn($this->repoAcquisition);
        $this->transactionPersistence->method('getRepositoryForMovement')->willReturn($this->repoMovement);
        $this->transactionPersistence->method('getRepositoryForLiquidation')->willReturn($this->repoLiquidation);
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('sameId')->willReturn(true);
        $this->currency->method('getDecimals')->willReturn(2);
        $this->currency->method('getIso3')->willReturn('EUR');
        $this->account = $this->createStub(Account::class);
        $this->price = new StockPriceVO('4.5600', $this->currency);
        $this->expenses = new TransactionExpenseVO('23.34', $this->currency);
        $this->stock = $this->createStub(Stock::class);
        $this->stock->method('getCurrency')->willReturn($this->currency);
        $this->stock->method('getPrice')->willReturn($this->price);
        $this->stock->method('sameId')->willReturn(true);
    }

    public function testCreate(): void
    {
        $this->repoLiquidation->expects($this->once())->method('beginTransaction');
        $this->repoLiquidation->expects($this->once())->method('persist');
        $this->repoLiquidation->expects($this->once())->method('flush');
        $this->repoLiquidation->expects($this->once())->method('commit');

        /** @var Liquidation&MockObject */
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $this->assertInstanceOf(Liquidation::class, $transaction);
        $this->assertSame($this->stock, $transaction->getStock());
        $this->assertEquals(self::$dateTimeUtc->format('Y-m-d H:i:s'), $transaction->getDateTimeUtc()->format('Y-m-d H:i:s'));
        $this->assertSame(self::$amount->getValue(), $transaction->getAmount()->getValue());
        $this->assertEquals($this->price, $transaction->getPrice());
        $this->assertEquals($this->expenses, $transaction->getExpenses());
        $this->assertSame($this->account, $transaction->getAccount());
        $this->assertInstanceOf(Uuid::class, $transaction->getId());
        $this->assertSame($this->currency, $transaction->getCurrency());
        $this->assertTrue($transaction->sameId($transaction));
        $this->assertSame(self::$amount->getValue(), $transaction->getAmountActionable()->getValue());
        $this->assertEquals($this->expenses, $transaction->getExpensesUnaccountedFor());
    }

    public function testCreateWithSameAccountStockAndDatetimeWillThrowException(): void
    {
        $repoLiquidation = $this->createStub(LiquidationRepositoryInterface::class);
        $repoLiquidation->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(false);
        $transactionPersistence = $this->createStub(TransactionPersistenceInterface::class);
        $transactionPersistence->method('getRepositoryForLiquidation')->willReturn($repoLiquidation);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('transExistsOnDateTime');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testDateInFutureThrowsException(): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('futureDateNotAllowed');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), new \DateTime('tomorrow', new \DateTimeZone('UTC')), self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    #[DataProvider('invalidAmount')]
    public function testInvalidAmountFormatThrowsException($transAmount): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('enterNumberBetween');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, new TransactionAmountVO($transAmount), $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public static function invalidAmount(): array
    {
        return [
            ['1000000000'],
            ['-1'],
            ['0'],
        ];
    }

    public function testMovementWithWrongLiquidationThrowsException(): void
    {
        $transaction1 = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction2 = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $movement = $this->createStub(Movement::class);
        $movement->method('getLiquidation')->willReturn($transaction2);
        $this->expectException(\InvalidArgumentException::class);
        $transaction1->accountMovement($this->transactionPersistence->getRepositoryForLiquidation(), $movement);
    }

    public function testSameIdWithIncorrectEntityArgumentThrowsException(): void
    {
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $entity = new class implements EntityInterface {
            public function sameId(EntityInterface $otherEntity): bool
            {
                return true;
            }
        };
        $this->expectException(\InvalidArgumentException::class);
        $transaction->sameId($entity);
    }

    public function testWrongExpensesCurrencyThrowsException(): void
    {
        $expenses = $this->createStub(TransactionExpenseVO::class);
        $expenses->method('getCurrency')->willReturn($this->createStub(Currency::class));
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('otherCurrencyExpected');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testAccountMovementAndClearMovements(): void
    {
        $this->repoMovement->expects($this->once())->method('remove');
        $this->repoMovement->expects($this->once())->method('flush');
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance', 'sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getAmount')->willReturn(self::$amount);
        $movement->expects($this->exactly(2))->method('getLiquidationExpenses')->willReturn($this->expenses);
        $this->assertSame($transaction, $transaction->accountMovement($this->transactionPersistence->getRepositoryForLiquidation(), $movement));
        $this->assertSame('0', $transaction->getAmountActionable()->getValue());
        $this->assertEquals(new TransactionExpenseVO('0.00', $this->currency), $transaction->getExpensesUnaccountedFor());
        $acquisitionsCollection = $transaction->clearMovementCollection($this->transactionPersistence);
        $this->assertInstanceOf(AcquisitionCollection::class, $acquisitionsCollection);
        $this->assertSame(self::$amount->getValue(), $transaction->getAmountActionable()->getValue());
        $this->assertEquals($this->expenses, $transaction->getExpensesUnaccountedFor());
    }

    public function testMovementWithWrongExpensesAmountThrowsException(): void
    {
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance', 'sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getLiquidationExpenses')->willReturn($this->expenses->add(new TransactionExpenseVO('1', $this->currency)));
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('InvalidMovementExpensesAmount');
        $transaction->accountMovement($this->transactionPersistence->getRepositoryForLiquidation(), $movement);
    }

    public function testMovementAmountGreaterThanAmountRemainingThrowsException(): void
    {
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance', 'sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getAmount')->willReturn(
            new TransactionAmountVO(bcadd(self::$amount->getValue(), '1'))
        );
        $movement->expects($this->once())->method('getLiquidationExpenses')->willReturn(new TransactionExpenseVO('4.56', $this->currency));
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('MovementAmountNotWithinAllowedLimits');
        $transaction->accountMovement($this->transactionPersistence->getRepositoryForLiquidation(), $movement);
    }

    public function testCreateIsRolledBackOnTransactionException(): void
    {
        $this->repoLiquidation->expects($this->once())->method('beginTransaction');
        $this->repoLiquidation->expects($this->once())->method('commit')->willThrowException(new \Exception('simulating uncached exception'));
        $this->repoLiquidation->expects($this->once())->method('rollBack');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('simulating uncached exception');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testPersistRemove(): void
    {
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $this->repoLiquidation->expects($this->once())->method('beginTransaction');
        $this->repoLiquidation->expects($this->once())->method('remove');
        $this->repoLiquidation->expects($this->once())->method('flush');
        $this->repoLiquidation->expects($this->once())->method('commit');
        $transaction->persistRemove($this->transactionPersistence);
    }

    public function testRemoveIsRolledBackOnTransactionException(): void
    {
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $this->repoLiquidation->expects($this->once())->method('beginTransaction');
        $this->repoLiquidation->expects($this->once())->method('remove')->willThrowException(new \Exception('simulating uncached exception'));
        $this->repoLiquidation->expects($this->once())->method('rollBack');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('simulating uncached exception');
        $transaction->persistRemove($this->transactionPersistence);
    }

    public function testExceptionIsThrownOnCreateCommitFail(): void
    {
        $this->repoLiquidation->expects($this->once())->method('commit')->willThrowException(new \Exception('simulating uncached exception'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('simulating uncached exception');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testExceptionIsThrownOnRemoveCommitFail(): void
    {
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $this->repoLiquidation->expects($this->once())->method('remove')->willThrowException(new \Exception('simulating uncached exception'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('simulating uncached exception');
        $transaction->persistRemove($this->transactionPersistence);
    }

    public function testDomainExceptionWhileInCreateTransactionThrowsDomainException(): void
    {
        $this->repoLiquidation->expects($this->once())->method('persist')->willThrowException(new \Exception('simulating exception is thrown'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('simulating exception is thrown');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testDomainExceptionWhileInRemoveTransactionThrowsDomainException(): void
    {
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->transactionPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction->expects($this->once())->method('fiFoCriteriaInstance')->willThrowException(new \Exception('simulating exception is thrown'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('simulating exception is thrown');
        $transaction->persistRemove($this->transactionPersistence);
    }
}
