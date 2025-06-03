<?php

declare(strict_types=1);

namespace Tests\integration\Stock\Infrastructure\Transaction\Accounting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Component\Uid\Uuid;
use Tests\integration\IntegrationTestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Infrastructure\Doctrine\AccountPersistence;
use Xver\MiCartera\Domain\Account\Infrastructure\Doctrine\AccountRepository;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine\CurrencyPersistence;
use Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine\CurrencyRepository;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Exchange\Domain\Exchange;
use Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangePersistence;
use Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangeRepository;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\SummaryDTO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\SummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Criteria\FiFoCriteria;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAbstract;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting\MovementRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\TransactionPersistence;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;

/**
 * @internal
 */
#[CoversClass(MovementRepository::class)]
#[UsesClass(Account::class)]
#[UsesClass(AccountPersistence::class)]
#[UsesClass(Exchange::class)]
#[UsesClass(ExchangePersistence::class)]
#[UsesClass(Movement::class)]
#[UsesClass(MovementCollection::class)]
#[UsesClass(SummaryVO::class)]
#[UsesClass(SummaryDTO::class)]
#[UsesClass(Currency::class)]
#[UsesClass(CurrencyPersistence::class)]
#[UsesClass(TransactionExpenseVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(Stock::class)]
#[UsesClass(StockPersistence::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(Acquisition::class)]
#[UsesClass(AcquisitionCollection::class)]
#[UsesClass(FiFoCriteria::class)]
#[UsesClass(Liquidation::class)]
#[UsesClass(TransactionPersistence::class)]
#[UsesClass(TransactionAbstract::class)]
#[UsesClass(TransactionAmountActionableVO::class)]
#[UsesClass(TransactionAmountVO::class)]
#[UsesClass(AccountRepository::class)]
#[UsesClass(EntityRepository::class)]
#[UsesClass(ExchangeRepository::class)]
#[UsesClass(CurrencyRepository::class)]
#[UsesClass(StockRepository::class)]
#[UsesClass(AcquisitionRepository::class)]
#[UsesClass(LiquidationRepository::class)]
#[UsesClass(TransactionExpenseVO::class)]
#[UsesClass(MovementPriceVO::class)]
class MovementRepositoryDoctrineTest extends IntegrationTestCase
{
    private TransactionPersistence $transactionPersistence;
    private Account $account;
    private Stock $stock;
    private TransactionExpenseVO $expenses;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->transactionPersistence = new TransactionPersistence(self::$registry);
        $repoAccount = new AccountRepository(self::$registry);
        $repoStock = new StockRepository(self::$registry);
        $this->account = $repoAccount->findByIdentifier('test@example.com');
        $this->stock = $repoStock->findById('SAN');
        $this->expenses = new TransactionExpenseVO('11.43', $this->account->getCurrency());
    }

    public function testFindByIdOrThowException(): void
    {
        self::$loadFixtures = true;
        $acquisition = new Acquisition($this->transactionPersistence, $this->stock, $this->stock->getPrice(), new \DateTime('30 mins ago', new \DateTimeZone('UTC')), new TransactionAmountVO('100'), $this->expenses, $this->account);
        $liquidation = new Liquidation($this->transactionPersistence, $this->stock, $this->stock->getPrice(), new \DateTime('20 mins ago', new \DateTimeZone('UTC')), new TransactionAmountVO('100'), $this->expenses, $this->account);
        $movement = $this->transactionPersistence->getRepositoryForMovement()->findByIdOrThrowException($acquisition->getId(), $liquidation->getId());
        $this->assertInstanceOf(Movement::class, $movement);
        $this->assertSame($acquisition, $movement->getAcquisition());
        $this->assertSame($liquidation, $movement->getLiquidation());
    }

    public function testFindByIdOrThowExceptionWhenNotFoundThrowsException(): void
    {
        try {
            $entity = 'Movement';
            $uuid1 = Uuid::v4();
            $uuid2 = Uuid::v4();
            $this->transactionPersistence->getRepositoryForMovement()->findByIdOrThrowException($uuid1, $uuid2);
        } catch (EntityNotFoundException $th) {
            $this->assertSame('entityNotFound', $th->getTranslatableMessage()->getMessage());
            $this->assertSame(['entity' => $entity, 'identifier' => $uuid1->toString().' '.$uuid2->toString()], $th->getTranslatableMessage()->getParameters());
            $this->assertSame('PhpAppCore', $th->getTranslatableMessage()->getDomain());
        }
    }

    public function testFindByAccountAndYear(): void
    {
        self::$loadFixtures = true;
        $lastYear = (int) new \DateTime('last year', new \DateTimeZone('UTC'))->format('Y');
        $movementCollection = $this->transactionPersistence->getRepositoryForMovement()->findByAccountAndYear($this->account, $lastYear);
        $this->assertInstanceOf(MovementCollection::class, $movementCollection);
        $this->assertSame(0, $movementCollection->count());
        $dateAcquisition = new \DateTime('first day of january '.$lastYear, new \DateTimeZone('UTC'));
        $dateLiquidation = (clone $dateAcquisition)->add(new \DateInterval('PT1S'));
        $acquisition = new Acquisition($this->transactionPersistence, $this->stock, $this->stock->getPrice(), $dateAcquisition, new TransactionAmountVO('100'), $this->expenses, $this->account);
        $liquidation = new Liquidation($this->transactionPersistence, $this->stock, $this->stock->getPrice(), $dateLiquidation, new TransactionAmountVO('100'), $this->expenses, $this->account);
        $movementCollection = $this->transactionPersistence->getRepositoryForMovement()->findByAccountAndYear($this->account, $lastYear, null);
        $this->assertSame(1, $movementCollection->count());
        $this->assertTrue($acquisition->sameId($movementCollection->offsetGet(0)->getAcquisition()));
        $this->assertTrue($liquidation->sameId($movementCollection->offsetGet(0)->getLiquidation()));
    }

    public function testFindByAccountStockAcquisitionDateAfter(): void
    {
        self::$loadFixtures = true;
        $movementCollection = $this->transactionPersistence->getRepositoryForMovement()->findByAccountStockAcquisitionDateAfter($this->account, $this->stock, new \DateTime('yesterday', new \DateTimeZone('UTC')));
        $this->assertInstanceOf(MovementCollection::class, $movementCollection);
        $this->assertSame(0, $movementCollection->count());
        $acquisition = new Acquisition($this->transactionPersistence, $this->stock, $this->stock->getPrice(), new \DateTime('48 hours ago', new \DateTimeZone('UTC')), new TransactionAmountVO('100'), $this->expenses, $this->account);
        $liquidation = new Liquidation($this->transactionPersistence, $this->stock, $this->stock->getPrice(), new \DateTime('47 hours ago', new \DateTimeZone('UTC')), new TransactionAmountVO('100'), $this->expenses, $this->account);
        $movementCollection = $this->transactionPersistence->getRepositoryForMovement()->findByAccountStockAcquisitionDateAfter($this->account, $this->stock, new \DateTime('yesterday', new \DateTimeZone('UTC')));
        $this->assertSame(0, $movementCollection->count());
        $movementCollection = $this->transactionPersistence->getRepositoryForMovement()->findByAccountStockAcquisitionDateAfter($this->account, $this->stock, new \DateTime('3 days ago', new \DateTimeZone('UTC')));
        $this->assertSame(1, $movementCollection->count());
        $this->assertTrue($acquisition->sameId($movementCollection->offsetGet(0)->getAcquisition()));
        $this->assertTrue($liquidation->sameId($movementCollection->offsetGet(0)->getLiquidation()));
    }

    public function testAccountingSummaryByAccount(): void
    {
        $summary = $this->transactionPersistence->getRepositoryForMovement()->accountingSummaryByAccount($this->account, (int) new \DateTime('now', new \DateTimeZone('UTC'))->format('Y'));
        $this->assertInstanceOf(SummaryVO::class, $summary);
    }

    public function testRemoveDoesNotWriteToDatabase(): void
    {
        self::$loadFixtures = true;
        $acquisition = new Acquisition($this->transactionPersistence, $this->stock, $this->stock->getPrice(), new \DateTime('30 mins ago', new \DateTimeZone('UTC')), new TransactionAmountVO('100'), $this->expenses, $this->account);
        $liquidation = new Liquidation($this->transactionPersistence, $this->stock, $this->stock->getPrice(), new \DateTime('20 mins ago', new \DateTimeZone('UTC')), new TransactionAmountVO('100'), $this->expenses, $this->account);
        $movement = $this->transactionPersistence->getRepositoryForMovement()->findByIdOrThrowException($acquisition->getId(), $liquidation->getId());
        $this->transactionPersistence->getRepositoryForMovement()->remove($movement);
        parent::detachEntity($movement);
        $movement = $this->transactionPersistence->getRepositoryForMovement()->findByIdOrThrowException($acquisition->getId(), $liquidation->getId());
        $this->assertInstanceOf(Movement::class, $movement);
        $this->assertSame($acquisition, $movement->getAcquisition());
        $this->assertSame($liquidation, $movement->getLiquidation());
    }
}
