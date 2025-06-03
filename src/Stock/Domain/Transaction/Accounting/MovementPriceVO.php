<?php

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting;

use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;

/**
 * @psalm-api
 */
class MovementPriceVO extends MoneyVO
{
    /** @var numeric-string */
    protected string $valueMin = '0';
    protected int $maxDecimals = 4;
    protected string $numberPropertyName = 'movement';

    /**
     * @psalm-param numeric-string $value
     */
    public function __construct(string $value, Currency $currency)
    {
        parent::__construct($value, $currency);
    }
}
