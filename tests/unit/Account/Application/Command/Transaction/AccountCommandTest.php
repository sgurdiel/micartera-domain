<?php

declare(strict_types=1);

namespace Tests\unit\Account\Application\Command\Transaction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Application\Command\AccountCreateCommand;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Account\Domain\AccountRepositoryInterface;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyCollection;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(AccountCreateCommand::class)]
#[UsesClass(Account::class)]
#[UsesClass(CurrencyCollection::class)]
class AccountCommandTest extends TestCase
{
    private static \DateTimeZone $timezone;
    private static string $email;
    private static string $password;
    private static array $roles;
    private static bool $agreeTerms;
    private static string $currencyIso3;
    private AccountRepositoryInterface&Stub $repoAccount;
    private CurrencyRepositoryInterface&Stub $repoCurrency;
    private AccountPersistenceInterface&Stub $accountPersistence;

    public static function setUpBeforeClass(): void
    {
        self::$timezone = new \DateTimeZone('Europe/Madrid');
        self::$email = 'test@example.com';
        self::$password = 'password';
        self::$roles = ['ROLE_USER'];
        self::$agreeTerms = true;
        self::$currencyIso3 = 'EUR';
    }

    public function setUp(): void
    {
        $this->repoAccount = $this->createStub(AccountRepositoryInterface::class);
        $this->repoCurrency = $this->createStub(CurrencyRepositoryInterface::class);
        $this->accountPersistence = $this->createMock(AccountPersistenceInterface::class);
        $this->accountPersistence->method('getRepository')->willReturn($this->repoAccount);
        $this->accountPersistence->method('getRepositoryForCurrency')->willReturn($this->repoCurrency);
    }

    public function testCreateCommandExecutionSuccessfuly(): void
    {
        $currency = $this->createStub(Currency::class);
        $this->repoCurrency->method('findByIdOrThrowException')->willReturn($currency);
        $this->repoCurrency->method('findById')->willReturn($currency);
        $command = new AccountCreateCommand($this->accountPersistence);
        $account = $command->invoke(
            self::$email,
            self::$password,
            self::$currencyIso3,
            self::$timezone,
            self::$roles,
            self::$agreeTerms
        );
        $this->assertInstanceOf(Account::class, $account);
    }

    public function testCreatCommandNoAgreeTermsThrowsException(): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('mustAgreeTerms');
        $command = new AccountCreateCommand($this->accountPersistence);
        $command->invoke(
            self::$email,
            self::$password,
            self::$currencyIso3,
            self::$timezone,
            self::$roles,
            false
        );
    }

    public function testCreateCommandBadCurrencyThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('expectedPersistedEntityNotFound');
        $this->repoCurrency->method('findByIdOrThrowException')->willThrowException(new \Exception('expectedPersistedEntityNotFound'));
        $command = new AccountCreateCommand($this->accountPersistence);
        $command->invoke(
            self::$email,
            self::$password,
            self::$currencyIso3,
            self::$timezone,
            self::$roles,
            self::$agreeTerms
        );
    }

    public function testCreateCommandBadNewAccountThrowsException(): void
    {
        $this->expectException(DomainViolationException::class);
        $currency = $this->createStub(Currency::class);
        $this->repoCurrency->method('findByIdOrThrowException')->willReturn($currency);
        $this->repoCurrency->method('findById')->willReturn($currency);
        $command = new AccountCreateCommand($this->accountPersistence);
        $command->invoke(
            'invalidemail',
            self::$password,
            self::$currencyIso3,
            self::$timezone,
            self::$roles,
            self::$agreeTerms
        );
    }
}
