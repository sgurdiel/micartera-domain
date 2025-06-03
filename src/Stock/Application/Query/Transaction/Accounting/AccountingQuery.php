<?php

namespace Xver\MiCartera\Domain\Stock\Application\Query\Transaction\Accounting;

use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionPersistenceInterface;

final class AccountingQuery
{
    public function __construct(
        private AccountPersistenceInterface $accountPersistence,
        private TransactionPersistenceInterface $transactionPersistence
    ) {}

    public function byAccountYear(
        string $accountIdentifier,
        ?int $displayedYear = null,
        int $limit = 0,
        int $page = 0,
    ): AccountingDTO {
        $account = $this->accountPersistence->getRepository()->findByIdentifierOrThrowException($accountIdentifier);
        $displayedYear = (
            is_null($displayedYear)
            ? (int) (new \DateTime('now', $account->getTimeZone()))->format('Y')
            : $displayedYear
        );

        return new AccountingDTO(
            $account,
            $this->transactionPersistence->getRepositoryForMovement()->findByAccountAndYear(
                $account,
                $displayedYear,
                $limit ? $limit + 1 : null,
                $limit ? $page * $limit : 0
            ),
            $displayedYear,
            $this->transactionPersistence->getRepositoryForMovement()->accountingSummaryByAccount($account, $displayedYear),
            $limit,
            $page
        );
    }
}
