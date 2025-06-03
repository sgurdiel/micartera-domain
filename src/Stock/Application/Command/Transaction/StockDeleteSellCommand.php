<?php

namespace Xver\MiCartera\Domain\Stock\Application\Command\Transaction;

use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionPersistenceInterface;

final class StockDeleteSellCommand
{
    public function __construct(private TransactionPersistenceInterface $transactionPersistence) {}

    public function invoke(
        string $id
    ): void {
        $this->transactionPersistence->getRepositoryForLiquidation()->findByIdOrThrowException(
            new Uuid($id)
        )->persistRemove(
            $this->transactionPersistence
        );
    }
}
