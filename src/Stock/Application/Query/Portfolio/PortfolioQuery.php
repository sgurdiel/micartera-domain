<?php

namespace Xver\MiCartera\Domain\Stock\Application\Query\Portfolio;

use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Portfolio\SummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionPersistenceInterface;

final class PortfolioQuery
{
    public function __construct(
        private StockPersistenceInterface $stockPersistence,
        private AccountPersistenceInterface $accountPersistence,
        private TransactionPersistenceInterface $transactionPersistence
    ) {}

    public function getPortfolio(
        string $accountIdentifier,
        int $limit = 0,
        int $page = 0,
    ): PortfolioDTO {
        $account = $this->accountPersistence->getRepository()->findByIdentifierOrThrowException($accountIdentifier);

        return new PortfolioDTO(
            $account,
            $this->transactionPersistence->getRepository()->findByAccountWithActionableAmount(
                $account,
                'ASC',
                'datetimeutc',
                $limit ? $limit + 1 : 0,
                $limit ? $page * $limit : 0,
            ),
            $this->transactionPersistence->getRepository()->portfolioSummary($account),
            $limit,
            $page
        );
    }

    public function getStockPortfolioSummary(
        string $accountIdentifier,
        string $stockCode
    ): SummaryVO {
        $account = $this->accountPersistence->getRepository()->findByIdentifierOrThrowException($accountIdentifier);
        $stock = $this->stockPersistence->getRepository()->findById($stockCode);

        return $this->transactionPersistence->getRepository()->portfolioSummary($account, $stock);
    }
}
