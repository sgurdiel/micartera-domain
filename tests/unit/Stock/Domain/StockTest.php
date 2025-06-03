<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Exchange\Domain\Exchange;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\StockRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(Stock::class)]
#[UsesClass(Account::class)]
#[UsesClass(Currency::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(StockPriceVO::class)]
class StockTest extends TestCase
{
    private Currency&Stub $currency;
    private StockPriceVO&Stub $stockPrice;
    private MockObject&StockRepositoryInterface $repoStock;
    private AcquisitionRepositoryInterface&Stub $repoAcquisition;
    private Exchange&Stub $exchange;
    private StockPersistenceInterface&Stub $stockPersistence;

    public function setUp(): void
    {
        $this->repoAcquisition = $this->createStub(AcquisitionRepositoryInterface::class);
        $this->repoStock = $this->createMock(StockRepositoryInterface::class);
        $this->stockPersistence = $this->createStub(StockPersistenceInterface::class);
        $this->stockPersistence->method('getRepository')->willReturn($this->repoStock);
        $this->stockPersistence->method('getRepositoryForAcquisition')->willReturn($this->repoAcquisition);
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('getDecimals')->willReturn(2);
        $this->stockPrice = $this->createStub(StockPriceVO::class);
        $this->stockPrice->method('getCurrency')->willReturn($this->currency);
        $this->stockPrice->method('getValue')->willReturn('4.5614');
        $this->exchange = $this->createStub(Exchange::class);
    }

    public function testStockObjectIsCreated(): void
    {
        $this->currency->method('sameId')->willReturn(true);
        $name = 'ABCD Name';
        $stock = new Stock($this->stockPersistence, 'ABCD', $name, $this->stockPrice, $this->exchange);
        $this->assertInstanceOf(Stock::class, $stock);
        $this->assertTrue($stock->sameId($stock));
        $this->assertSame($name, $stock->getName());
        $this->assertSame('4.5614', $stock->getPrice()->getValue());
        $this->assertSame($this->currency, $stock->getCurrency());
        $this->assertSame('ABCD', $stock->getId());
        $this->assertSame($this->exchange, $stock->getExchange());
    }

    public function testDuplicateStockCodeThrowsException(): void
    {
        $stock = new Stock($this->stockPersistence, 'ABCD', 'ABCD Name', $this->stockPrice, $this->exchange);
        $this->repoStock->method('findById')->willReturn($stock);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('stockExists');
        new Stock($this->stockPersistence, 'ABCD', 'ABCD Name', $this->stockPrice, $this->exchange);
    }

    #[DataProvider('invalidCodes')]
    public function testStockCodeFormat($code): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('stringLength');
        new Stock($this->stockPersistence, $code, 'ABCD Name', $this->stockPrice, $this->exchange);
    }

    public static function invalidCodes(): array
    {
        return [
            [''], ['ABCDE'],
        ];
    }

    #[DataProvider('invalidNames')]
    public function testStockNameFormat($name): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('stringLength');
        new Stock($this->stockPersistence, 'ABCD', $name, $this->stockPrice, $this->exchange);
    }

    public static function invalidNames(): array
    {
        $name = '';
        for ($i = 0; $i < 256; ++$i) {
            $name .= mt_rand(0, 9);
        }

        return [
            [''], [$name],
        ];
    }

    public function testUpdateStockPriceWithInvalidCurrencyThrowsException(): void
    {
        $this->currency->method('sameId')->willReturn(false);
        $stock = new Stock($this->stockPersistence, 'ABCD', 'ABCD Name', $this->stockPrice, $this->exchange);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('otherCurrencyExpected');
        $stock->persistUpdate($this->stockPersistence, $stock->getName(), $this->stockPrice);
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        $stock = new Stock($this->stockPersistence, 'ABCD', 'ABCD Name', $this->stockPrice, $this->exchange);
        $entity = new class implements EntityInterface {
            public function sameId(EntityInterface $otherEntity): bool
            {
                return true;
            }
        };
        $this->expectException(\InvalidArgumentException::class);
        $stock->sameId($entity);
    }

    public function testSetPrice(): void
    {
        $this->currency->method('sameId')->willReturn(true);
        $stock = new Stock($this->stockPersistence, 'ABCD', 'ABCD Name', $this->stockPrice, $this->exchange);

        /** @var StockPriceVO&Stub */
        $newStockPrice = $this->createStub(StockPriceVO::class);
        $newStockPrice->method('getValue')->willReturn('6.7824');
        $stock->persistUpdate($this->stockPersistence, $stock->getName(), $newStockPrice);
        $this->assertSame('6.7824', $stock->getPrice()->getValue());
    }

    public function testRemoveWhenHavingTransactionsWillThrowException(): void
    {
        /** @var AcquisitionsCollection&Stub */
        $acquisitionsColletion = $this->createStub(AcquisitionCollection::class);
        $acquisitionsColletion->method('count')->willReturn(1);
        $this->repoAcquisition->method('findByStockId')->willReturn($acquisitionsColletion);

        /** @var Stock */
        $stock = $this->getMockBuilder(Stock::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('stockHasTransactions');
        $stock->persistRemove($this->stockPersistence);
    }

    public function testRemove(): void
    {
        $this->repoStock->expects($this->once())->method('remove');

        /** @var AcquisitionsCollection&Stub */
        $acquisitionsColletion = $this->createStub(AcquisitionCollection::class);
        $acquisitionsColletion->method('count')->willReturn(0);
        $this->repoAcquisition->method('findByStockId')->willReturn($acquisitionsColletion);
        $stock = $this->getMockBuilder(Stock::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $stock->persistRemove($this->stockPersistence);
    }

    public function testUpdate(): void
    {
        $currency = $this->createStub(Currency::class);
        $currency->method('sameId')->willReturn(true);
        $this->repoStock->expects($this->once())->method('persist');
        $this->repoStock->expects($this->once())->method('flush');

        /** @var AcquisitionsCollection&Stub */
        $acquisitionsColletion = $this->createStub(AcquisitionCollection::class);
        $acquisitionsColletion->method('count')->willReturn(0);
        $this->repoAcquisition->method('findByStockId')->willReturn($acquisitionsColletion);

        /** @var MockObject&Stock */
        $stock = $this->getMockBuilder(Stock::class)->disableOriginalConstructor()->onlyMethods(['getCurrency'])->getMock();
        $stock->method('getCurrency')->willReturn($currency);
        $stock->persistUpdate($this->stockPersistence, 'newName', new StockPriceVO('37.21', $this->stockPrice->getCurrency()));
    }

    public function testExceptionIsThrownOnCreateCommitFail(): void
    {
        $this->repoStock->expects($this->once())->method('persist')->willThrowException(new \Exception('simulating uncached exception'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('simulating uncached exception');
        new Stock($this->stockPersistence, 'ABCD', 'ABCD Name', $this->stockPrice, $this->exchange);
    }

    public function testExceptionIsThrownOnUpdateCommitFail(): void
    {
        $currency = $this->createStub(Currency::class);
        $currency->method('sameId')->willReturn(true);

        /** @var MockObject&Stock */
        $stock = $this->getMockBuilder(Stock::class)->disableOriginalConstructor()->onlyMethods(['getCurrency'])->getMock();
        $stock->method('getCurrency')->willReturn($currency);
        $this->repoStock->expects($this->once())->method('persist')->willThrowException(new \Exception('simulating uncached exception'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('simulating uncached exception');
        $stock->persistUpdate($this->stockPersistence, 'newName', new StockPriceVO('33.4451', $this->stockPrice->getCurrency()));
    }

    public function testExceptionIsThrownOnRemoveCommitFail(): void
    {
        /** @var Stock */
        $stock = $this->getMockBuilder(Stock::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $this->repoStock->expects($this->once())->method('remove')->willThrowException(new \Exception('simulating uncached exception'));

        /** @var AcquisitionsCollection&Stub */
        $acquisitionsColletion = $this->createStub(AcquisitionCollection::class);
        $acquisitionsColletion->method('count')->willReturn(0);
        $this->repoAcquisition->method('findByStockId')->willReturn($acquisitionsColletion);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('simulating uncached exception');
        $stock->persistRemove($this->stockPersistence);
    }
}
