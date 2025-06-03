<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Application\Command\Transaction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Account\Domain\AccountRepositoryInterface;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\StockCreatePurchaseCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\StockCreateSellCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\StockDeletePurchaseCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\StockDeleteSellCommand;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\StockRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Criteria\FiFoCriteria;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionPersistenceInterface;

/**
 * @internal
 */
#[CoversClass(StockCreatePurchaseCommand::class)]
#[CoversClass(StockCreateSellCommand::class)]
#[CoversClass(StockDeletePurchaseCommand::class)]
#[CoversClass(StockDeleteSellCommand::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(Stock::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(Acquisition::class)]
#[UsesClass(AcquisitionCollection::class)]
#[UsesClass(FiFoCriteria::class)]
#[UsesClass(Liquidation::class)]
#[UsesClass(TransactionAmountVO::class)]
#[UsesClass(TransactionExpenseVO::class)]
class StockOperateCommandTest extends TestCase
{
    private Currency&Stub $currency;
    private Account&Stub $account;
    private Stock&Stub $stock;
    private MockObject&StockRepositoryInterface $repoStock;
    private AccountRepositoryInterface&MockObject $repoAccount;
    private MockObject&MovementRepositoryInterface $repoMovement;
    private AcquisitionRepositoryInterface&MockObject $repoAcquisition;
    private LiquidationRepositoryInterface&MockObject $repoLiquidation;
    private Stub&TransactionPersistenceInterface $transactionPersistence;
    private AccountPersistenceInterface&Stub $accountPersistence;
    private StockPersistenceInterface&Stub $stockPersistence;

    public function setUp(): void
    {
        $this->repoStock = $this->createMock(StockRepositoryInterface::class);
        $this->repoAccount = $this->createMock(AccountRepositoryInterface::class);
        $this->repoMovement = $this->createMock(MovementRepositoryInterface::class);
        $this->repoAcquisition = $this->createMock(AcquisitionRepositoryInterface::class);
        $this->repoLiquidation = $this->createMock(LiquidationRepositoryInterface::class);
        $this->transactionPersistence = $this->createStub(TransactionPersistenceInterface::class);
        $this->transactionPersistence->method('getRepository')->willReturn($this->repoAcquisition);
        $this->transactionPersistence->method('getRepositoryForMovement')->willReturn($this->repoMovement);
        $this->transactionPersistence->method('getRepositoryForLiquidation')->willReturn($this->repoLiquidation);
        $this->accountPersistence = $this->createStub(AccountPersistenceInterface::class);
        $this->accountPersistence->method('getRepository')->willReturn($this->repoAccount);
        $this->stockPersistence = $this->createStub(StockPersistenceInterface::class);
        $this->stockPersistence->method('getRepository')->willReturn($this->repoStock);
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('sameId')->willReturn(true);
        $this->currency->method('getDecimals')->willReturn(2);
        $this->account = $this->createStub(Account::class);
        $this->account->method('getCurrency')->willReturn($this->currency);
        $this->account->method('getTimeZone')->willReturn(new \DateTimeZone('Europe/Madrid'));
        $this->stock = $this->createStub(Stock::class);
        $this->stock->method('getCurrency')->willReturn($this->currency);
    }

    public function testPurchaseCommandSucceeds(): void
    {
        $this->expectNotToPerformAssertions();
        $this->repoAcquisition->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        $this->repoStock->method('findByIdOrThrowException')->willReturn($this->stock);
        $this->repoAccount->method('findByIdentifierOrThrowException')->willReturn($this->account);
        $command = $this->getMockBuilder(StockCreatePurchaseCommand::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->transactionPersistence, $this->accountPersistence, $this->stockPersistence])
            ->getMock()
        ;
        $acquisition = $command->invoke(
            'TEST',
            new \DateTime('now', new \DateTimeZone('UTC')),
            '100',
            '6.5443',
            '5.34',
            'test@example.com'
        );
    }

    public function testRemovePurchaseCommandSucceeds(): void
    {
        $uuid = Uuid::v4();

        /** @var Acquisition&MockObject */
        $transaction = $this->createMock(Acquisition::class);
        $transaction->expects($this->once())->method('persistRemove');
        $this->repoAcquisition->method('findByIdOrThrowException')->willReturn($transaction);
        $command = new StockDeletePurchaseCommand($this->transactionPersistence);
        $command->invoke($uuid->toRfc4122());
    }

    public function testSellCommandSucceeds(): void
    {
        $this->repoLiquidation->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        $this->repoStock->method('findByIdOrThrowException')->willReturn($this->stock);
        $this->repoAccount->method('findByIdentifierOrThrowException')->willReturn($this->account);
        $command = $this->getMockBuilder(StockCreateSellCommand::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->transactionPersistence, $this->accountPersistence, $this->stockPersistence])
            ->getMock()
        ;
        $liquidation = $command->invoke(
            'TEST',
            new \DateTime('now', new \DateTimeZone('UTC')),
            '100',
            '6.5443',
            '5.34',
            'test@example.com'
        );
        $this->assertInstanceOf(Liquidation::class, $liquidation);
    }

    public function testRemoveSellCommandSucceeds(): void
    {
        $uuid = Uuid::v4();

        /** @var Liquidation&MockObject */
        $transaction = $this->createMock(Liquidation::class);
        $transaction->expects($this->once())->method('persistRemove');
        $this->repoLiquidation->method('findByIdOrThrowException')->willReturn($transaction);
        $command = new StockDeleteSellCommand($this->transactionPersistence);
        $command->invoke($uuid->toRfc4122());
    }
}
