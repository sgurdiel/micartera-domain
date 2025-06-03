<?php

namespace Tests\unit\Account\Application\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Application\Query\AccountQuery;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Account\Domain\AccountRepositoryInterface;

/**
 * @internal
 */
#[CoversClass(AccountQuery::class)]
class AccountQueryTest extends TestCase
{
    private AccountPersistenceInterface&Stub $accountPersistence;
    private AccountRepositoryInterface&Stub $accountRepo;
    private AccountQuery $accountQuery;

    protected function setUp(): void
    {
        $this->accountPersistence = $this->createStub(AccountPersistenceInterface::class);
        $this->accountRepo = $this->createStub(AccountRepositoryInterface::class);

        $this->accountPersistence->method('getRepository')->willReturn($this->accountRepo);

        $this->accountQuery = new AccountQuery($this->accountPersistence);
    }

    public function testFindByIdentifierOrThrowExceptionReturnsAccount(): void
    {
        $identifier = 'test-identifier';
        $expectedAccount = $this->createMock(Account::class);

        $this->accountRepo
            ->method('findByIdentifierOrThrowException')
            ->with($identifier)
            ->willReturn($expectedAccount)
        ;

        $result = $this->accountQuery->findByIdentifierOrThrowException($identifier);

        $this->assertSame($expectedAccount, $result);
    }

    public function testFindByIdentifierOrThrowExceptionThrowsException(): void
    {
        $this->expectException(\Exception::class);

        $identifier = 'non-existent-identifier';

        $this->accountRepo
            ->method('findByIdentifierOrThrowException')
            ->with($identifier)
            ->willThrowException(new \Exception('Account not found'))
        ;

        $this->accountQuery->findByIdentifierOrThrowException($identifier);
    }
}
