<?php

declare(strict_types=1);

namespace Tests\unit\Exchange\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Exchange\Domain\Exchange;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangeCollection;

/**
 * @internal
 */
#[CoversClass(ExchangeCollection::class)]
class ExchangesCollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $exchangesCollection = new ExchangeCollection([]);
        $this->assertSame(Exchange::class, $exchangesCollection->type());
    }
}
