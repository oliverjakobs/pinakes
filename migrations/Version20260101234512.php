<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260101234512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE boardgame (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, publisher_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, min_player INTEGER NOT NULL, max_player INTEGER DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CONSTRAINT FK_98A1DB1D40C86FCE FOREIGN KEY (publisher_id) REFERENCES boardgame_publisher (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_98A1DB1D40C86FCE ON boardgame (publisher_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE boardgame_publisher (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE boardgame
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE boardgame_publisher
        SQL);
    }
}
