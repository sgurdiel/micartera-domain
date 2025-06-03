<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Transaction\Accounting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementCollection;

/**
 * @internal
 */
#[CoversClass(MovementCollection::class)]
class MovementsCollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $accountingMovementsCollection = new MovementCollection([]);
        $this->assertSame(Movement::class, $accountingMovementsCollection->type());
    }
}
