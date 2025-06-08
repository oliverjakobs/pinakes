<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250608193013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE series (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE series_volume (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, series_id INTEGER DEFAULT NULL, book_id INTEGER DEFAULT NULL, volume INTEGER NOT NULL, CONSTRAINT FK_DAE92EE35278319C FOREIGN KEY (series_id) REFERENCES series (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DAE92EE316A2B381 FOREIGN KEY (book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DAE92EE35278319C ON series_volume (series_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_DAE92EE316A2B381 ON series_volume (book_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__book_author AS SELECT book_id, author_id FROM book_author
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE book_author
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE book_author (book_id INTEGER NOT NULL, author_id INTEGER NOT NULL, PRIMARY KEY(book_id, author_id), CONSTRAINT FK_9478D34516A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9478D345F675F31B FOREIGN KEY (author_id) REFERENCES author (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO book_author (book_id, author_id) SELECT book_id, author_id FROM __temp__book_author
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__book_author
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9478D34516A2B381 ON book_author (book_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9478D345F675F31B ON book_author (author_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE series
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE series_volume
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__book_author AS SELECT book_id, author_id FROM book_author
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE book_author
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE book_author (book_id INTEGER NOT NULL, author_id INTEGER NOT NULL, PRIMARY KEY(book_id, author_id), CONSTRAINT FK_9478D34516A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9478D345F675F31B FOREIGN KEY (author_id) REFERENCES author (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO book_author (book_id, author_id) SELECT book_id, author_id FROM __temp__book_author
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__book_author
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2F0A2BEE16A2B381 ON book_author (book_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2F0A2BEEF675F31B ON book_author (author_id)
        SQL);
    }
}
