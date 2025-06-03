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
 */
abstract class EntityRepository extends ServiceEntityRepository implements EntityRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry, private string $entityClass)
    {
        parent::__construct($managerRegistry, $this->entityClass);
    }

    /**
     * @param T $entity
     */
    public function persist(EntityInterface $entity): self
    {
        $this->validateRepositoryCanOperateEntity($entity);
        $this->getEntityManager()->persist($entity);

        return $this;
    }

    /**
     * @param T $entity
     */
    public function remove(EntityInterface $entity): self
    {
        $this->validateRepositoryCanOperateEntity($entity);
        $this->getEntityManager()->remove($entity);

        return $this;
    }

    public function flush(): self
    {
        $this->getEntityManager()->flush();

        return $this;
    }

    public function beginTransaction(): self
    {
        $this->getEntityManager()->beginTransaction();

        return $this;
    }

    public function commit(): self
    {
        $this->getEntityManager()->commit();

        return $this;
    }

    public function rollBack(): self
    {
        $this->getEntityManager()->rollback();

        return $this;
    }

    /**
     * @param T $entity
     */
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
