<?php

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityCollection;

/**
 * @template-extends EntityCollection<Movement>
 *
 * @psalm-api
 */
class MovementCollection extends EntityCollection
{
    #[\Override]
    public function type(): string
    {
        return Movement::class;
    }
}
