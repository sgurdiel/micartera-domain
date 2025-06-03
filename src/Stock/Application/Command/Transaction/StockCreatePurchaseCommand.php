<?php

namespace Xver\MiCartera\Domain\Stock\Application\Command\Transaction;

use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionPersistenceInterface;

/**
 * @psalm-api
 */
class StockCreatePurchaseCommand
{
    public function __construct(
        private TransactionPersistenceInterface $transactionPersistence,
        private AccountPersistenceInterface $accountPersistence,
        private StockPersistenceInterface $stockPersistence
    ) {}

    /**
     * @psalm-param numeric-string $amount
     * @psalm-param numeric-string $priceValue
     * @psalm-param numeric-string $expensesValue
     */
    public function invoke(
        string $stockCode,
        \DateTime $datetimeutc,
        string $amount,
        string $priceValue,
        string $expensesValue,
        string $accountIdentifier
    ): Acquisition {
        $account = $this->accountPersistence->getRepository()->findByIdentifierOrThrowException($accountIdentifier);
        $stock = $this->stockPersistence->getRepository()->findByIdOrThrowException($stockCode);

        return new Acquisition(
            $this->transactionPersistence,
            $stock,
            new StockPriceVO(
                $priceValue,
                $account->getCurrency()
            ),
            $datetimeutc,
            new TransactionAmountVO($amount),
            new TransactionExpenseVO(
                $expensesValue,
                $account->getCurrency()
            ),
            $account
        );
    }
}
