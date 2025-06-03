<?php

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @psalm-api
 */
class Acquisition extends TransactionAbstract
{
    public function __construct(
        private readonly TransactionPersistenceInterface $transactionPersistence,
        Stock $stock,
        StockPriceVO $acquisitionPrice,
        \DateTime $datetimeutc,
        TransactionAmountVO $amount,
        TransactionExpenseVO $expenses,
        Account $account
    ) {
        parent::__construct($stock, $acquisitionPrice, $datetimeutc, $amount, $expenses, $account);
        $this->persistCreate();
    }

    #[\Override]
    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof Acquisition) {
            throw new \InvalidArgumentException();
        }

        return parent::getId()->equals(
            $otherEntity->getId()
        );
    }

    public function accountMovement(
        AcquisitionRepositoryInterface $repoAcquisition,
        Movement $movement
    ): self {
        if (false === $this->sameId($movement->getAcquisition())) {
            throw new \InvalidArgumentException();
        }
        $this->decreaseAmountActionable(new TransactionAmountActionableVO($movement->getAmount()->getValue()));
        $this->decreaseExpensesUnaccountedFor($movement->getAcquisitionExpenses());
        $repoAcquisition->persist($this);

        return $this;
    }

    public function unaccountMovement(
        AcquisitionRepositoryInterface $repoAcquisition,
        Movement $movement
    ): self {
        if (false === $this->sameId($movement->getAcquisition())) {
            throw new \InvalidArgumentException();
        }
        $this->increaseAmountActionable(new TransactionAmountActionableVO($movement->getAmount()->getValue()));
        $this->increaseExpensesUnaccountedFor($movement->getAcquisitionExpenses());
        $repoAcquisition->persist($this);

        return $this;
    }

    #[\Override]
    protected function persistCreate(): void
    {
        $repoAcquisition = $this->transactionPersistence->getRepository();
        if (
            false === $repoAcquisition->assertNoTransWithSameAccountStockOnDateTime(
                $this->getAccount(),
                $this->getStock(),
                $this->getDateTimeUtc()
            )
        ) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'transExistsOnDateTime',
                    [],
                    'MiCarteraDomain'
                ),
                'acquisition.duplicate'
            );
        }
        $repoAcquisition->beginTransaction();

        try {
            $this->fiFoCriteriaInstance(
                $this->transactionPersistence
            )->onAcquisition($this);
            $repoAcquisition->persist($this);
            $repoAcquisition->flush();
            $repoAcquisition->commit();
        } catch (\Throwable $th) {
            $repoAcquisition->rollBack();

            throw $th;
        }
    }

    public function persistRemove(
        TransactionPersistenceInterface $transactionPersistence
    ): void {
        if ($this->getAmount()->different($this->getAmountActionable())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'transBuyCannotBeRemovedWithoutFullAmountOutstanding',
                    [],
                    'MiCarteraDomain'
                ),
                'acquisition.amountOutstanding'
            );
        }
        $repoAcquisition = $transactionPersistence->getRepository();
        $repoAcquisition->remove($this);
        $repoAcquisition->flush();
    }
}
