<?php

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementCollection;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @psalm-api
 */
class Liquidation extends TransactionAbstract
{
    /** @var MovementCollection */
    private Collection $movementCollection;

    public function __construct(
        private readonly TransactionPersistenceInterface $transactionPersistence,
        Stock $stock,
        StockPriceVO $liquidationPrice,
        \DateTime $datetimeutc,
        TransactionAmountVO $amount,
        TransactionExpenseVO $expenses,
        Account $account
    ) {
        parent::__construct($stock, $liquidationPrice, $datetimeutc, $amount, $expenses, $account);
        $this->movementCollection = new MovementCollection([]);
        $this->persistCreate();
    }

    #[\Override]
    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof Liquidation) {
            throw new \InvalidArgumentException();
        }

        return parent::getId()->equals($otherEntity->getId());
    }

    public function clearMovementCollection(
        TransactionPersistenceInterface $transactionPersistence
    ): AcquisitionCollection {
        $updatedAcquisitionsCollection = new AcquisitionCollection([]);
        $repoMovement = $transactionPersistence->getRepositoryForMovement();
        $repoAcquisition = $transactionPersistence->getRepository();
        $repoLiquidation = $transactionPersistence->getRepositoryForLiquidation();
        foreach ($this->movementCollection->toArray() as $movement) {
            $acquisition = $movement->getAcquisition();
            $acquisition->unaccountMovement(
                $repoAcquisition,
                $movement
            );
            if (false === $updatedAcquisitionsCollection->contains($acquisition)) {
                $updatedAcquisitionsCollection->add($acquisition);
            }
            $repoMovement->remove($movement);
            $repoMovement->flush();
            parent::increaseExpensesUnaccountedFor($movement->getLiquidationExpenses());
        }
        $this->movementCollection->clear();
        $this->amountActionable = new TransactionAmountActionableVO($this->amount->getValue());
        $repoLiquidation->persist($this);

        return $updatedAcquisitionsCollection;
    }

    public function accountMovement(
        LiquidationRepositoryInterface $repoLiquidation,
        Movement $movement
    ): self {
        if (false === $this->sameId($movement->getLiquidation())) {
            throw new \InvalidArgumentException();
        }
        parent::decreaseExpensesUnaccountedFor($movement->getLiquidationExpenses()); // TODO: parent::? should be $this->
        $this->decreaseAmountActionable(new TransactionAmountActionableVO($movement->getAmount()->getValue()));
        $this->movementCollection->add($movement);
        $repoLiquidation->persist($this);

        return $this;
    }

    #[\Override]
    protected function persistCreate(): void
    {
        $repoLiquidation = $this->transactionPersistence->getRepositoryForLiquidation();
        if (
            false === $repoLiquidation->assertNoTransWithSameAccountStockOnDateTime(
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
                'liquidation.duplicate'
            );
        }
        $repoLiquidation->beginTransaction();

        try {
            $this->fiFoCriteriaInstance(
                $this->transactionPersistence
            )->onLiquidation($this);
            $repoLiquidation->persist($this);
            $repoLiquidation->flush();
            $repoLiquidation->commit();
        } catch (\Throwable $th) {
            $repoLiquidation->rollBack();

            throw $th;
        }
    }

    public function persistRemove(
        TransactionPersistenceInterface $transactionPersistence
    ): void {
        $repoLiquidation = $transactionPersistence->getRepositoryForLiquidation();
        $repoLiquidation->beginTransaction();

        try {
            $this->fiFoCriteriaInstance(
                $transactionPersistence
            )->onLiquidationRemoval($this);
            $repoLiquidation->remove($this);
            $repoLiquidation->flush();
            $repoLiquidation->commit();
        } catch (\Throwable $th) {
            $repoLiquidation->rollBack();

            throw $th;
        }
    }
}
