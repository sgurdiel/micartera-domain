<?php

namespace Xver\MiCartera\Domain\Stock\Domain;

use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

interface StockPersistenceInterface extends EntityPersistenceInterface
{
    /**
     * @return StockRepositoryInterface
     */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface;

    /**
     * @return AcquisitionRepositoryInterface
     */
    public function getRepositoryForAcquisition(): EntityRepositoryInterface;
}
