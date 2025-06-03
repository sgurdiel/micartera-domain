<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250524221431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE stockAccountingMovement CHANGE acquisitionPrice acquisition_price NUMERIC(16, 4) UNSIGNED NOT NULL, CHANGE liquidationPrice liquidation_price NUMERIC(16, 4) UNSIGNED NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stockTransactionAcquisition CHANGE expenses_unaccounted_for unaccounted_expenses NUMERIC(10, 4) UNSIGNED NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE stockAccountingMovement CHANGE acquisition_price acquisitionPrice NUMERIC(16, 4) UNSIGNED NOT NULL, CHANGE liquidation_price liquidationPrice NUMERIC(16, 4) UNSIGNED NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stockTransactionAcquisition CHANGE unaccounted_expenses expenses_unaccounted_for NUMERIC(10, 4) UNSIGNED NOT NULL
        SQL);
    }
}
