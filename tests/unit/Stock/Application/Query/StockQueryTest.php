<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Application\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Account\Domain\AccountRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Application\Query\StockQuery;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockCollection;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Application\Query\EntityCollectionQueryResponse;

/**
 * @internal
 */
#[CoversClass(StockQuery::class)]
class StockQueryTest extends TestCase
{
    private AccountRepositoryInterface&Stub $repoAccount;
    private StockRepositoryInterface&Stub $repoStock;
    private StockPersistenceInterface&Stub $stockPersistence;
    private AccountPersistenceInterface&Stub $accountPersistence;

    public function setUp(): void
    {
        $this->repoAccount = $this->createStub(AccountRepositoryInterface::class);
        $this->repoStock = $this->createStub(StockRepositoryInterface::class);
        $this->stockPersistence = $this->createStub(StockPersistenceInterface::class);
        $this->stockPersistence->method('getRepository')->willReturn($this->repoStock);
        $this->accountPersistence = $this->createStub(AccountPersistenceInterface::class);
        $this->accountPersistence->method('getRepository')->willReturn($this->repoAccount);
    }

    public function testByAccountsCurrencyQuerySucceeds(): void
    {
        $query = new StockQuery($this->stockPersistence, $this->accountPersistence);
        $response = $query->byAccountsCurrency(
            '',
            0,
            0
        );
        $this->assertInstanceOf(EntityCollectionQueryResponse::class, $response);
        $this->assertInstanceOf(StockCollection::class, $response->getCollection());

        $query = new StockQuery($this->stockPersistence, $this->accountPersistence);
        $response = $query->byAccountsCurrency(
            '',
            10,
            0
        );
        $this->assertInstanceOf(EntityCollectionQueryResponse::class, $response);
        $this->assertInstanceOf(StockCollection::class, $response->getCollection());
    }

    public function testByCodeQuerySucceeds(): void
    {
        $query = new StockQuery($this->stockPersistence, $this->accountPersistence);
        $response = $query->byCode('');
        $this->assertInstanceOf(Stock::class, $response);
    }
}
