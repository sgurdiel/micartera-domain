<?php

namespace Xver\MiCartera\Domain\Stock\Application\Command\Transaction;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @psalm-api
 */
class StockOperationImportCommand
{
    public function __construct(
        private TransactionPersistenceInterface $transactionPersistence,
        private AccountPersistenceInterface $accountPersistence,
        private StockPersistenceInterface $stockPersistence
    ) {}

    /**
     * @psalm-param array{0: string,1: string,2: string,3: numeric-string,4: numeric-string,5: numeric-string} $line date<'Y-m-d H:i:s T'>,type,stock,price,amount,expenses
     */
    public function invoke(
        int $lineNumber,
        array $line,
        string $accountIdentifier
    ): void {
        $account = $this->accountPersistence->getRepository()->findByIdentifierOrThrowException($accountIdentifier);

        try {
            $type = $line[1];
            $this->validTransactionType($type);
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $line[0], $account->getTimeZone());
            $this->validDateTime($dateTime);
            if ('UTC' !== $account->getTimeZone()->getName()) {
                $dateTime->setTimezone(new \DateTimeZone('UTC'));
            }
            $command
                = 'acquisition' === $type
                ? new StockCreatePurchaseCommand($this->transactionPersistence, $this->accountPersistence, $this->stockPersistence)
                : new StockCreateSellCommand($this->transactionPersistence, $this->accountPersistence, $this->stockPersistence);
            $command->invoke(
                $line[2],
                $dateTime,
                $line[4],
                $line[3],
                $line[5],
                $accountIdentifier
            );
        } catch (EntityNotFoundException $e) {
            $this->throwDomainViolationException($lineNumber, 'stock', $e->getTranslatableMessage());
        } catch (DomainViolationException $th) {
            $this->throwDomainViolationException($lineNumber, $th->getDomainEntail(), $th->getTranslatableMessage());
        }
    }

    private function throwDomainViolationException(int $lineNumber, ?string $field, TranslatableMessage $error): void
    {
        throw new DomainViolationException(
            new TranslatableMessage(
                'importCsvDomainError',
                [
                    'row' => $lineNumber,
                    'field' => $field,
                    'error' => $error,
                ],
                'MiCarteraDomain'
            ),
            'transaction'
        );
    }

    private function validTransactionType(string $type): void
    {
        if (false === in_array($type, ['acquisition', 'liquidation'], true)) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'invalidTransactionType',
                    ['type' => $type],
                    'MiCarteraDomain'
                ),
                'transaction.type'
            );
        }
    }

    private function validDateTime(\DateTime|false $dateTime): void
    {
        if (false === $dateTime) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'failedCreatingDateObjectFromString',
                    ['format' => 'Y-m-d H:i:s'],
                    'MiCarteraDomain'
                ),
                'transaction.datetimeutc'
            );
        }
    }
}
