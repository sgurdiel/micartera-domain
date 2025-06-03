<?php

declare(strict_types=1);

namespace Tests\unit\Currency\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyCollection;

/**
 * @internal
 */
#[CoversClass(CurrencyCollection::class)]
class CurrenciesCollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $currenciesCollection = new CurrencyCollection([]);
        $this->assertSame(Currency::class, $currenciesCollection->type());
    }
}
