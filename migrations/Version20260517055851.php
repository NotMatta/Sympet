<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260517055851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cart (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, session_token VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL, CONSTRAINT FK_BA388B7A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_BA388B7A76ED395 ON cart (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CART_USER ON cart (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CART_SESSION_TOKEN ON cart (session_token)');
        $this->addSql('CREATE TABLE cart_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quantity INTEGER NOT NULL, added_at DATETIME NOT NULL, cart_id INTEGER NOT NULL, product_id INTEGER NOT NULL, CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F0FE25274584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F0FE25271AD5CDBF ON cart_item (cart_id)');
        $this->addSql('CREATE INDEX IDX_F0FE25274584665A ON cart_item (product_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CART_ITEM_PRODUCT ON cart_item (cart_id, product_id)');
        $this->addSql('CREATE TABLE "order" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, order_number VARCHAR(64) NOT NULL, total DOUBLE PRECISION NOT NULL, status VARCHAR(32) NOT NULL, stripe_session_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5299398551F0F81 ON "order" (order_number)');
        $this->addSql('CREATE INDEX IDX_F5299398A76ED395 ON "order" (user_id)');
        $this->addSql('CREATE TABLE order_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, product_name VARCHAR(255) NOT NULL, unit_price DOUBLE PRECISION NOT NULL, quantity INTEGER NOT NULL, order_id INTEGER NOT NULL, product_id INTEGER DEFAULT NULL, CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES "order" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_52EA1F098D9F6D38 ON order_item (order_id)');
        $this->addSql('CREATE INDEX IDX_52EA1F094584665A ON order_item (product_id)');
        $this->addSql('CREATE TABLE saved_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, session_token VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL, product_id INTEGER NOT NULL, CONSTRAINT FK_1124D70BA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1124D70B4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1124D70BA76ED395 ON saved_item (user_id)');
        $this->addSql('CREATE INDEX IDX_1124D70B4584665A ON saved_item (product_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SAVED_USER_PRODUCT ON saved_item (user_id, product_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SAVED_SESSION_PRODUCT ON saved_item (session_token, product_id)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, is_verified BOOLEAN NOT NULL, verification_token VARCHAR(255) DEFAULT NULL, reset_token VARCHAR(255) DEFAULT NULL, reset_token_expires_at DATETIME DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, github_id VARCHAR(255) DEFAULT NULL, avatar_url VARCHAR(1024) DEFAULT NULL, display_name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE cart');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE saved_item');
        $this->addSql('DROP TABLE "user"');
    }
}
