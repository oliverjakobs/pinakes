<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251109203746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__book AS SELECT id, publisher_id, title, published, first_published, isbn FROM book
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE book
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE book (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, publisher_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, published INTEGER DEFAULT NULL, first_published INTEGER DEFAULT NULL, isbn VARCHAR(13) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CONSTRAINT FK_CBE5A33140C86FCE FOREIGN KEY (publisher_id) REFERENCES publisher (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO book (id, publisher_id, title, published, first_published, isbn) SELECT id, publisher_id, title, published, first_published, isbn FROM __temp__book
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__book
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CBE5A33140C86FCE ON book (publisher_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__book AS SELECT id, publisher_id, title, published, first_published, isbn FROM book
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE book
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE book (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, publisher_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, published INTEGER DEFAULT NULL, first_published INTEGER DEFAULT NULL, isbn VARCHAR(13) DEFAULT NULL, CONSTRAINT FK_CBE5A33140C86FCE FOREIGN KEY (publisher_id) REFERENCES publisher (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO book (id, publisher_id, title, published, first_published, isbn) SELECT id, publisher_id, title, published, first_published, isbn FROM __temp__book
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__book
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CBE5A33140C86FCE ON book (publisher_id)
        SQL);
    }
}
