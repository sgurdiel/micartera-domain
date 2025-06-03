<?php

declare(strict_types=1);

namespace Tests\integration\Account\Infrastructure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\integration\IntegrationTestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Infrastructure\Doctrine\AccountPersistence;
use Xver\MiCartera\Domain\Account\Infrastructure\Doctrine\AccountRepository;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine\CurrencyRepository;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Criteria\FiFoCriteria;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAbstract;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;

/**
 * @internal
 */
#[CoversClass(AccountRepository::class)]
#[UsesClass(Account::class)]
#[UsesClass(AccountPersistence::class)]
#[UsesClass(Currency::class)]
#[UsesClass(CurrencyRepository::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Stock::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(Acquisition::class)]
#[UsesClass(AcquisitionCollection::class)]
#[UsesClass(FiFoCriteria::class)]
#[UsesClass(TransactionAbstract::class)]
class AccountRepositoryDoctrineTest extends IntegrationTestCase
{
    private AccountPersistence $accountPersistence;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();

        $this->accountPersistence = new AccountPersistence(self::$registry);
    }

    public function testAccountIsPersisted(): void
    {
        $account = new Account(
            $this->accountPersistence,
            'test4@example.com',
            'password',
            $this->accountPersistence->getRepositoryForCurrency()->findByIdOrThrowException('EUR'),
            new \DateTimeZone('Europe/Madrid'),
            ['ROLE_USER']
        );
        $accountId = $account->getIdentifier();
        parent::detachEntity($account);
        $this->assertInstanceOf(Account::class, $this->accountPersistence->getRepository()->findByIdentifier($accountId));
    }

    public function testFindByIdentifierOrThrowsException(): void
    {
        $email = 'test@example.com';
        $account = $this->accountPersistence->getRepository()->findByIdentifierOrThrowException($email);
        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals($email, $account->getEmail());
    }

    public function testFindByIdentifierOrThrowsExceptionWhenNotFoundWillThrowException(): void
    {
        try {
            $entity = 'Account';
            $id = 'nonexistent@example.com';
            $this->accountPersistence->getRepository()->findByIdentifierOrThrowException($id);
        } catch (EntityNotFoundException $th) {
            $this->assertSame('entityNotFound', $th->getTranslatableMessage()->getMessage());
            $this->assertSame(['entity' => $entity, 'identifier' => $id], $th->getTranslatableMessage()->getParameters());
            $this->assertSame('PhpAppCore', $th->getTranslatableMessage()->getDomain());
        }
    }

    public function testFindByIdentifier(): void
    {
        $email = 'test@example.com';
        $account = $this->accountPersistence->getRepository()->findByIdentifier($email);
        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals($email, $account->getEmail());
    }
}
