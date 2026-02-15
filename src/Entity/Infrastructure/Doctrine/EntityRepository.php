<?php

namespace Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Translation\TranslatableMessage;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @template T of EntityInterface
 *
 * @template-extends ServiceEntityRepository<T>
 * @template-implements EntityRepositoryInterface<T>
 */
abstract class EntityRepository extends ServiceEntityRepository implements EntityRepositoryInterface
{
    /**
     * @param class-string<T> $entityClass
     */
    public function __construct(ManagerRegistry $managerRegistry, private string $entityClass)
    {
        parent::__construct($managerRegistry, $this->entityClass);
    }

    /**
     * @param T $entity
     */
    #[\Override]
    public function persist(EntityInterface $entity): self
    {
        $this->validateRepositoryCanOperateEntity($entity);
        $this->getEntityManager()->persist($entity);

        return $this;
    }

    /**
     * @param T $entity
     */
    #[\Override]
    public function remove(EntityInterface $entity): self
    {
        $this->validateRepositoryCanOperateEntity($entity);
        $this->getEntityManager()->remove($entity);

        return $this;
    }

    #[\Override]
    public function flush(): self
    {
        $this->getEntityManager()->flush();

        return $this;
    }

    #[\Override]
    public function beginTransaction(): self
    {
        $this->getEntityManager()->beginTransaction();

        return $this;
    }

    #[\Override]
    public function commit(): self
    {
        $this->getEntityManager()->commit();

        return $this;
    }

    #[\Override]
    public function rollBack(): self
    {
        $this->getEntityManager()->rollback();

        return $this;
    }

    private function validateRepositoryCanOperateEntity(EntityInterface $entity): void
    {
        if (!$entity instanceof $this->entityClass) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'cannotOperateEntityUsingRepository',
                    ['entity' => get_class($entity), 'repository' => get_class($this)]
                )
            );
        }
    }
}
