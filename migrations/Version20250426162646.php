<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250426162646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE brand (id SERIAL NOT NULL, image_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_1C52F9583DA5256D ON brand (image_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE category (id SERIAL NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_64C19C1727ACA70 ON category (parent_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE category_category (category_source INT NOT NULL, category_target INT NOT NULL, PRIMARY KEY(category_source, category_target))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B1369DBA5062B508 ON category_category (category_source)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B1369DBA4987E587 ON category_category (category_target)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE form (id SERIAL NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE manufacturer (id SERIAL NOT NULL, image_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(1000) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_3D0AE6DC3DA5256D ON manufacturer (image_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE media_file (id SERIAL NOT NULL, product_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, size INT DEFAULT NULL, mime_type VARCHAR(255) DEFAULT NULL, url VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4FD8E9C34584665A ON media_file (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE price (id SERIAL NOT NULL, quantity_id INT NOT NULL, unit_price DOUBLE PRECISION DEFAULT NULL, total_price DOUBLE PRECISION DEFAULT NULL, currency VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_CAC822D97E8B4AFC ON price (quantity_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE product (id SERIAL NOT NULL, form_id INT DEFAULT NULL, brand_id INT DEFAULT NULL, manufacturer_id INT DEFAULT NULL, price_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) DEFAULT NULL, description VARCHAR(2000) DEFAULT NULL, is_active BOOLEAN NOT NULL, variants TEXT DEFAULT NULL, active BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D34A04AD5FF69B7D ON product (form_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D34A04AD44F5D008 ON product (brand_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D34A04ADA23B42D ON product (manufacturer_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_D34A04ADD614C7E7 ON product (price_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN product.variants IS '(DC2Type:array)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE product_category (product_id INT NOT NULL, category_id INT NOT NULL, PRIMARY KEY(product_id, category_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CDFC73564584665A ON product_category (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CDFC735612469DE2 ON product_category (category_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE product_restricted (product_id INT NOT NULL, restricted_id INT NOT NULL, PRIMARY KEY(product_id, restricted_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_54ABDF6C4584665A ON product_restricted (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_54ABDF6CBAC54862 ON product_restricted (restricted_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE quantity (id SERIAL NOT NULL, unit_id INT DEFAULT NULL, count DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9FF31636F8BD700D ON quantity (unit_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE restricted (id SERIAL NOT NULL, waiting_for VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE unit (id SERIAL NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE brand ADD CONSTRAINT FK_1C52F9583DA5256D FOREIGN KEY (image_id) REFERENCES media_file (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category_category ADD CONSTRAINT FK_B1369DBA5062B508 FOREIGN KEY (category_source) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category_category ADD CONSTRAINT FK_B1369DBA4987E587 FOREIGN KEY (category_target) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE manufacturer ADD CONSTRAINT FK_3D0AE6DC3DA5256D FOREIGN KEY (image_id) REFERENCES media_file (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE media_file ADD CONSTRAINT FK_4FD8E9C34584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE price ADD CONSTRAINT FK_CAC822D97E8B4AFC FOREIGN KEY (quantity_id) REFERENCES quantity (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD CONSTRAINT FK_D34A04AD5FF69B7D FOREIGN KEY (form_id) REFERENCES form (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD CONSTRAINT FK_D34A04AD44F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD CONSTRAINT FK_D34A04ADA23B42D FOREIGN KEY (manufacturer_id) REFERENCES manufacturer (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD CONSTRAINT FK_D34A04ADD614C7E7 FOREIGN KEY (price_id) REFERENCES price (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_category ADD CONSTRAINT FK_CDFC73564584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_category ADD CONSTRAINT FK_CDFC735612469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_restricted ADD CONSTRAINT FK_54ABDF6C4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_restricted ADD CONSTRAINT FK_54ABDF6CBAC54862 FOREIGN KEY (restricted_id) REFERENCES restricted (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quantity ADD CONSTRAINT FK_9FF31636F8BD700D FOREIGN KEY (unit_id) REFERENCES unit (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD active BOOLEAN DEFAULT true NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER created_at DROP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER updated_at DROP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".created_at IS NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".updated_at IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE brand DROP CONSTRAINT FK_1C52F9583DA5256D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category DROP CONSTRAINT FK_64C19C1727ACA70
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category_category DROP CONSTRAINT FK_B1369DBA5062B508
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category_category DROP CONSTRAINT FK_B1369DBA4987E587
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE manufacturer DROP CONSTRAINT FK_3D0AE6DC3DA5256D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE media_file DROP CONSTRAINT FK_4FD8E9C34584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE price DROP CONSTRAINT FK_CAC822D97E8B4AFC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product DROP CONSTRAINT FK_D34A04AD5FF69B7D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product DROP CONSTRAINT FK_D34A04AD44F5D008
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product DROP CONSTRAINT FK_D34A04ADA23B42D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product DROP CONSTRAINT FK_D34A04ADD614C7E7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_category DROP CONSTRAINT FK_CDFC73564584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_category DROP CONSTRAINT FK_CDFC735612469DE2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_restricted DROP CONSTRAINT FK_54ABDF6C4584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product_restricted DROP CONSTRAINT FK_54ABDF6CBAC54862
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quantity DROP CONSTRAINT FK_9FF31636F8BD700D
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE brand
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category_category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE form
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE manufacturer
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE media_file
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE price
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE product
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE product_category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE product_restricted
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE quantity
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE restricted
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE unit
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP active
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER created_at SET NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER updated_at SET NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
    }
}
