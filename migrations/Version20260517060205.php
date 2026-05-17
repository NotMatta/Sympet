<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260517060205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__cart AS SELECT id, session_token, created_at, updated_at, user_id FROM cart');
        $this->addSql('DROP TABLE cart');
        $this->addSql('CREATE TABLE cart (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, session_token VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL, CONSTRAINT FK_BA388B7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO cart (id, session_token, created_at, updated_at, user_id) SELECT id, session_token, created_at, updated_at, user_id FROM __temp__cart');
        $this->addSql('DROP TABLE __temp__cart');
        $this->addSql('CREATE INDEX IDX_BA388B7A76ED395 ON cart (user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__cart_item AS SELECT id, quantity, added_at, cart_id, product_id FROM cart_item');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('CREATE TABLE cart_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quantity INTEGER NOT NULL, added_at DATETIME NOT NULL, cart_id INTEGER NOT NULL, product_id INTEGER NOT NULL, CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F0FE25274584665A FOREIGN KEY (product_id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO cart_item (id, quantity, added_at, cart_id, product_id) SELECT id, quantity, added_at, cart_id, product_id FROM __temp__cart_item');
        $this->addSql('DROP TABLE __temp__cart_item');
        $this->addSql('CREATE INDEX IDX_F0FE25274584665A ON cart_item (product_id)');
        $this->addSql('CREATE INDEX IDX_F0FE25271AD5CDBF ON cart_item (cart_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__saved_item AS SELECT id, session_token, created_at, user_id, product_id FROM saved_item');
        $this->addSql('DROP TABLE saved_item');
        $this->addSql('CREATE TABLE saved_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, session_token VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL, product_id INTEGER NOT NULL, CONSTRAINT FK_1124D70BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1124D70B4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO saved_item (id, session_token, created_at, user_id, product_id) SELECT id, session_token, created_at, user_id, product_id FROM __temp__saved_item');
        $this->addSql('DROP TABLE __temp__saved_item');
        $this->addSql('CREATE INDEX IDX_1124D70B4584665A ON saved_item (product_id)');
        $this->addSql('CREATE INDEX IDX_1124D70BA76ED395 ON saved_item (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__cart AS SELECT id, session_token, created_at, updated_at, user_id FROM cart');
        $this->addSql('DROP TABLE cart');
        $this->addSql('CREATE TABLE cart (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, session_token VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL, CONSTRAINT FK_BA388B7A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO cart (id, session_token, created_at, updated_at, user_id) SELECT id, session_token, created_at, updated_at, user_id FROM __temp__cart');
        $this->addSql('DROP TABLE __temp__cart');
        $this->addSql('CREATE INDEX IDX_BA388B7A76ED395 ON cart (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CART_SESSION_TOKEN ON cart (session_token)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CART_USER ON cart (user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__cart_item AS SELECT id, quantity, added_at, cart_id, product_id FROM cart_item');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('CREATE TABLE cart_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quantity INTEGER NOT NULL, added_at DATETIME NOT NULL, cart_id INTEGER NOT NULL, product_id INTEGER NOT NULL, CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F0FE25274584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO cart_item (id, quantity, added_at, cart_id, product_id) SELECT id, quantity, added_at, cart_id, product_id FROM __temp__cart_item');
        $this->addSql('DROP TABLE __temp__cart_item');
        $this->addSql('CREATE INDEX IDX_F0FE25271AD5CDBF ON cart_item (cart_id)');
        $this->addSql('CREATE INDEX IDX_F0FE25274584665A ON cart_item (product_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CART_ITEM_PRODUCT ON cart_item (cart_id, product_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__saved_item AS SELECT id, session_token, created_at, user_id, product_id FROM saved_item');
        $this->addSql('DROP TABLE saved_item');
        $this->addSql('CREATE TABLE saved_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, session_token VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL, product_id INTEGER NOT NULL, CONSTRAINT FK_1124D70BA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1124D70B4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO saved_item (id, session_token, created_at, user_id, product_id) SELECT id, session_token, created_at, user_id, product_id FROM __temp__saved_item');
        $this->addSql('DROP TABLE __temp__saved_item');
        $this->addSql('CREATE INDEX IDX_1124D70BA76ED395 ON saved_item (user_id)');
        $this->addSql('CREATE INDEX IDX_1124D70B4584665A ON saved_item (product_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SAVED_SESSION_PRODUCT ON saved_item (session_token, product_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SAVED_USER_PRODUCT ON saved_item (user_id, product_id)');
    }
}
