<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521215546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE record (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, artist_id INTEGER DEFAULT NULL, label_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, medium VARCHAR(255) NOT NULL, released DATETIME DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CONSTRAINT FK_39986E43B7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_39986E4333B92F39 FOREIGN KEY (label_id) REFERENCES record_label (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_39986E43B7970CF8 ON record (artist_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_39986E4333B92F39 ON record (label_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE artist (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE record_label (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE record
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE artist
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE record_label
        SQL);
    }
}
