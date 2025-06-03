<?php

declare(strict_types=1);

namespace Tests\unit\Currency\Application\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Currency\Application\Query\CurrencyQuery;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyCollection;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyPersistenceInterface;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Application\Query\EntityCollectionQueryResponse;

/**
 * @internal
 */
#[CoversClass(CurrencyQuery::class)]
class CurrencyQueryTest extends TestCase
{
    private CurrencyPersistenceInterface&Stub $currencyPersistence;
    private CurrencyRepositoryInterface&Stub $repoCurrency;

    public function setUp(): void
    {
        $this->repoCurrency = $this->createStub(CurrencyRepositoryInterface::class);
        $this->currencyPersistence = $this->createMock(CurrencyPersistenceInterface::class);
        $this->currencyPersistence->method('getRepository')->willReturn($this->repoCurrency);
    }

    public function testByIdentifierQuerySucceeds(): void
    {
        $query = new CurrencyQuery($this->currencyPersistence);
        $response = $query->all();
        $this->assertInstanceOf(EntityCollectionQueryResponse::class, $response);
        $this->assertInstanceOf(CurrencyCollection::class, $response->getCollection());
    }
}
