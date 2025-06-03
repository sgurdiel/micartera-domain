<?php

namespace Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Exchange\Domain\Exchange;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangePersistenceInterface;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangeRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

final class ExchangePersistence implements ExchangePersistenceInterface
{
    public function __construct(private ManagerRegistry $managerRegistry) {}

    #[\Override]
    public function getRepository(): EntityRepositoryInterface
    {
        $repository = $this->managerRegistry->getRepository(Exchange::class);
        if (!$repository instanceof ExchangeRepositoryInterface) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'entityConfigurationContainsInvalidRepository'
                )
            );
        }

        return $repository;
    }
}
