<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250620164823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE category_category DROP CONSTRAINT fk_b1369dba4987e587
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category_category DROP CONSTRAINT fk_b1369dba5062b508
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category_category
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category ADD color VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quantity ALTER count TYPE INT
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE category_category (category_source INT NOT NULL, category_target INT NOT NULL, PRIMARY KEY(category_source, category_target))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_b1369dba4987e587 ON category_category (category_target)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_b1369dba5062b508 ON category_category (category_source)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category_category ADD CONSTRAINT fk_b1369dba4987e587 FOREIGN KEY (category_target) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category_category ADD CONSTRAINT fk_b1369dba5062b508 FOREIGN KEY (category_source) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category DROP color
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quantity ALTER count TYPE DOUBLE PRECISION
        SQL);
    }
}
