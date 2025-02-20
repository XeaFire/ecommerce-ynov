<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220132458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, seller_id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, description CLOB NOT NULL, price DOUBLE PRECISION NOT NULL, quantity INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , image VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_23A0E668DE820D9 FOREIGN KEY (seller_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_23A0E668DE820D9 ON article (seller_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, username, address, zip_code, city, phone, created_at FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, zip_code VARCHAR(10) NOT NULL, city VARCHAR(255) NOT NULL, phone VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , balance DOUBLE PRECISION NOT NULL, profile_picture VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password, username, address, zip_code, city, phone, created_at) SELECT id, email, roles, password, username, address, zip_code, city, phone, created_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE article');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, username, roles, password, address, zip_code, city, phone, created_at FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, zip_code VARCHAR(10) NOT NULL, city VARCHAR(255) NOT NULL, phone VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO user (id, email, username, roles, password, address, zip_code, city, phone, created_at) SELECT id, email, username, roles, password, address, zip_code, city, phone, created_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }
}
