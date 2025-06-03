<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250522163959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE stockTransactionAcquisition DROP FOREIGN KEY FK_3F3AB0CEE6DFE496
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_3F3AB0CEE6DFE496 ON stockTransactionAcquisition
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stockTransactionAcquisition DROP currency_iso3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stockTransactionLiquidation DROP FOREIGN KEY FK_CD2F14E2E6DFE496
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_CD2F14E2E6DFE496 ON stockTransactionLiquidation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stockTransactionLiquidation DROP currency_iso3
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE stockTransactionLiquidation ADD currency_iso3 VARCHAR(3) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stockTransactionLiquidation ADD CONSTRAINT FK_CD2F14E2E6DFE496 FOREIGN KEY (currency_iso3) REFERENCES currency (iso3) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CD2F14E2E6DFE496 ON stockTransactionLiquidation (currency_iso3)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stockTransactionAcquisition ADD currency_iso3 VARCHAR(3) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stockTransactionAcquisition ADD CONSTRAINT FK_3F3AB0CEE6DFE496 FOREIGN KEY (currency_iso3) REFERENCES currency (iso3) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3F3AB0CEE6DFE496 ON stockTransactionAcquisition (currency_iso3)
        SQL);
    }
}
