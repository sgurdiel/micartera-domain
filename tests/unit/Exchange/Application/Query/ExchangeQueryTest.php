<?php

declare(strict_types=1);

namespace Tests\unit\Exchange\Application\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Exchange\Application\Query\ExchangeQuery;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangeCollection;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangePersistenceInterface;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangeRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Application\Query\EntityCollectionQueryResponse;

/**
 * @internal
 */
#[CoversClass(ExchangeQuery::class)]
class ExchangeQueryTest extends TestCase
{
    private ExchangePersistenceInterface&Stub $exchangePersistence;
    private ExchangeRepositoryInterface&Stub $repoExchange;

    public function setUp(): void
    {
        $this->repoExchange = $this->createStub(ExchangeRepositoryInterface::class);
        $this->exchangePersistence = $this->createMock(ExchangePersistenceInterface::class);
        $this->exchangePersistence->method('getRepository')->willReturn($this->repoExchange);
    }

    public function testByIdentifierQuerySucceeds(): void
    {
        $query = new ExchangeQuery($this->exchangePersistence);
        $response = $query->all();
        $this->assertInstanceOf(EntityCollectionQueryResponse::class, $response);
        $this->assertInstanceOf(ExchangeCollection::class, $response->getCollection());
    }
}
