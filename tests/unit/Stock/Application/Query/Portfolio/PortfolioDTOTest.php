<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Application\Query\Portfolio;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Application\Query\Portfolio\PortfolioDTO;
use Xver\MiCartera\Domain\Stock\Domain\Portfolio\SummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\StockProfitVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(PortfolioDTO::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(StockProfitVO::class)]
#[UsesClass(MovementPriceVO::class)]
#[UsesClass(NumberOperation::class)]
class PortfolioDTOTest extends TestCase
{
    public function testPortfolio(): void
    {
        $currency = $this->createStub(Currency::class);
        $currency->method('sameId')->willReturn(true);

        $account = $this->createStub(Account::class);
        $account->method('getCurrency')->willReturn($currency);

        $price = $this->createStub(StockPriceVO::class);
        $price->method('getValue')->willReturn('0');

        $stock = $this->createStub(Stock::class);
        $stock->method('getPrice')->willReturn($price);

        $expensesUnaccountedFor = $this->createStub(TransactionExpenseVO::class);
        $expensesUnaccountedFor->method('getValue')->willReturn('0');

        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getPrice')->willReturn($price);
        $acquisition->method('getStock')->willReturn($stock);
        $acquisition->method('getExpensesUnaccountedFor')->willReturn($expensesUnaccountedFor);

        $outstandingPositionsCollection = $this->createStub(AcquisitionCollection::class);
        $outstandingPositionsCollection->method('offsetGet')->willReturn(
            $acquisition
        );

        $summary = $this->createStub(SummaryVO::class);
        $portfolio = new PortfolioDTO(
            $account,
            $outstandingPositionsCollection,
            $summary
        );
        $this->assertSame($account, $portfolio->getAccount());
        $this->assertSame($outstandingPositionsCollection, $portfolio->getCollection());
        $this->assertSame($summary, $portfolio->getSummary());
        $this->assertNotNull($portfolio->getPositionAcquisitionExpenses(0));
        $this->assertNotNull($portfolio->getPositionAcquisitionPrice(0));
        $this->assertNotNull($portfolio->getPositionMarketPrice(0));
        $this->assertNotNull($portfolio->getPositionProfitPrice(0));
        $this->assertNotNull($portfolio->getPositionProfitPercentage(0));
    }

    public function testEmptyPortfolio(): void
    {
        $currency = $this->createStub(Currency::class);

        /** @var Account&Stub */
        $account = $this->createStub(Account::class);
        $account->method('getCurrency')->willReturn($currency);
        $outstandingPositionsCollection = new AcquisitionCollection([]);

        /** @var Stub&SummaryVO */
        $summary = $this->createStub(SummaryVO::class);
        $portfolio = new PortfolioDTO(
            $account,
            $outstandingPositionsCollection,
            $summary
        );
        $this->assertSame($account, $portfolio->getAccount());
        $this->assertSame($outstandingPositionsCollection, $portfolio->getCollection());
        $this->assertSame($summary, $portfolio->getSummary());
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('collectionInvalidOffsetPosition');
        $this->assertNull($portfolio->getPositionAcquisitionExpenses(0));
    }
}
