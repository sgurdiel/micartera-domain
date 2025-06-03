<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Transaction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationCollection;

/**
 * @internal
 */
#[CoversClass(AcquisitionCollection::class)]
#[CoversClass(LiquidationCollection::class)]
class TransactionsCollectionTest extends TestCase
{
    public function testAcquisitionCollection(): void
    {
        $collection = new AcquisitionCollection([]);
        $this->assertSame(Acquisition::class, $collection->type());
    }

    public function testLiquidationCollection(): void
    {
        $collection = new LiquidationCollection([]);
        $this->assertSame(Liquidation::class, $collection->type());
    }
}
