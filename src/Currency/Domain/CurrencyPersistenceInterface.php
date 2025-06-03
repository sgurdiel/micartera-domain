<?php

namespace Xver\MiCartera\Domain\Currency\Domain;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

interface CurrencyPersistenceInterface extends EntityPersistenceInterface
{
    /**
     * @return CurrencyRepositoryInterface
     */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface;
}
