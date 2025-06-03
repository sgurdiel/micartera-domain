<?php

namespace Xver\MiCartera\Domain\Account\Domain;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;
use Xver\SymfonyAuthBundle\Account\Domain\AccountRepositoryInterface as DomainAccountRepositoryInterface;

interface AccountRepositoryInterface extends DomainAccountRepositoryInterface
{
    #[\Override]
    public function findByIdentifier(string $identifier): ?Account;

    /**
     * @throws EntityNotFoundException
     */
    #[\Override]
    public function findByIdentifierOrThrowException(string $identifier): Account;
}
