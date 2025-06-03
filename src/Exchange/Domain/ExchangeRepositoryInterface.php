<?php

namespace Xver\MiCartera\Domain\Exchange\Domain;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityRepositoryInterface<Exchange>
 */
interface ExchangeRepositoryInterface extends EntityRepositoryInterface
{
    public function findById(string $code): ?Exchange;

    public function findByIdOrThrowException(string $id): Exchange;

    public function all(): ExchangeCollection;
}
