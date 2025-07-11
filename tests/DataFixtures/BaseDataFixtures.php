<?php

namespace Tests\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Infrastructure\Doctrine\AccountPersistence;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine\CurrencyPersistence;
use Xver\MiCartera\Domain\Exchange\Domain\Exchange;
use Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangePersistence;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\TransactionPersistence;

class BaseDataFixtures extends Fixture
{
    public function __construct(private readonly ManagerRegistry $registry) {}

    public function load(ObjectManager $manager): void
    {
        $currencyPersitence = new CurrencyPersistence($this->registry);
        $currencyEuro = new Currency($currencyPersitence, 'EUR', '€', 2);
        new Currency($currencyPersitence, 'USD', '$', 2);
        $accountPersistence = new AccountPersistence($this->registry);
        $account = new Account(
            $accountPersistence,
            'test@example.com',
            'password1',
            $currencyEuro,
            new \DateTimeZone('Europe/Madrid'),
            ['ROLE_USER']
        );
        new Account(
            $accountPersistence,
            'test_other@example.com',
            'password2',
            $currencyEuro,
            new \DateTimeZone('America/Chicago'),
            ['ROLE_USER']
        );
        $exchangePersistence = new ExchangePersistence($this->registry);
        $exchange = new Exchange($exchangePersistence, 'MCE', 'Mercado Continuo Español');
        $price = new StockPriceVO('2.5620', $currencyEuro);
        $stockPersistence = new StockPersistence($this->registry);
        $stock = new Stock($stockPersistence, 'CABK', 'Caixabank', $price, $exchange);
        $price2 = new StockPriceVO('3.5620', $currencyEuro);
        new Stock($stockPersistence, 'SAN', 'Santander', $price2, $exchange);
        $price3 = new StockPriceVO('5.9620', $currencyEuro);
        new Stock($stockPersistence, 'ROVI', 'Laboratorios Rovi', $price3, $exchange);
        $transactionPersistence = new TransactionPersistence($this->registry);
        $dateAcquisition = new \DateTime('first day of january', new \DateTimeZone('UTC'));
        $dateLiquidation = (clone $dateAcquisition)->add(new \DateInterval('PT1S'));
        new Acquisition(
            $transactionPersistence,
            $stock,
            $price,
            $dateAcquisition,
            new TransactionAmountVO('200'),
            new TransactionExpenseVO('10.23', $currencyEuro),
            $account
        );
        new Liquidation(
            $transactionPersistence,
            $stock,
            $price,
            $dateLiquidation,
            new TransactionAmountVO('1'),
            new TransactionExpenseVO('1', $currencyEuro),
            $account
        );
    }
}
