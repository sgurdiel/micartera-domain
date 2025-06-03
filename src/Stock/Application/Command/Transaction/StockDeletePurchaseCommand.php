<?php

namespace Xver\MiCartera\Domain\Stock\Application\Command\Transaction;

use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionPersistenceInterface;

final class StockDeletePurchaseCommand
{
    public function __construct(private TransactionPersistenceInterface $transactionPersistence) {}

    public function invoke(
        string $acquisitionUuid
    ): void {
        $this->transactionPersistence->getRepository()->findByIdOrThrowException(
            new Uuid($acquisitionUuid)
        )->persistRemove(
            $this->transactionPersistence
        );
    }
}
