<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockCollection;

/**
 * @internal
 */
#[CoversClass(StockCollection::class)]
class StockCollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $stockCollection = new StockCollection([]);
        $this->assertSame(Stock::class, $stockCollection->type());
    }
}
