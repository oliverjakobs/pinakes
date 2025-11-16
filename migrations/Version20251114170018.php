<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114170018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE book_tag (book_id INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY(book_id, tag_id), CONSTRAINT FK_F2F4CE1516A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F2F4CE15BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F2F4CE1516A2B381 ON book_tag (book_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F2F4CE15BAD26311 ON book_tag (tag_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, color VARCHAR(9) DEFAULT '#ffffff' NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE book_genre
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE genre
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE series_volume
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__book AS SELECT id, publisher_id, title, published, first_published, isbn, created_at FROM book
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE book
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE book (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, publisher_id INTEGER DEFAULT NULL, series_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, published INTEGER DEFAULT NULL, first_published INTEGER DEFAULT NULL, isbn VARCHAR(13) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, series_volume INTEGER DEFAULT NULL, CONSTRAINT FK_CBE5A33140C86FCE FOREIGN KEY (publisher_id) REFERENCES publisher (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CBE5A3315278319C FOREIGN KEY (series_id) REFERENCES series (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO book (id, publisher_id, title, published, first_published, isbn, created_at) SELECT id, publisher_id, title, published, first_published, isbn, created_at FROM __temp__book
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__book
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CBE5A33140C86FCE ON book (publisher_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CBE5A3315278319C ON book (series_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE book_genre (book_id INTEGER NOT NULL, genre_id INTEGER NOT NULL, PRIMARY KEY(book_id, genre_id), CONSTRAINT FK_8D92268116A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8D9226814296D31F FOREIGN KEY (genre_id) REFERENCES genre (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D9226814296D31F ON book_genre (genre_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D92268116A2B381 ON book_genre (book_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE genre (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE "BINARY", color VARCHAR(9) DEFAULT '#ffffff' NOT NULL COLLATE "BINARY")
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE series_volume (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, series_id INTEGER DEFAULT NULL, book_id INTEGER DEFAULT NULL, volume INTEGER NOT NULL, CONSTRAINT FK_DAE92EE35278319C FOREIGN KEY (series_id) REFERENCES series (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DAE92EE316A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_DAE92EE316A2B381 ON series_volume (book_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DAE92EE35278319C ON series_volume (series_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE book_tag
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tag
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__book AS SELECT id, publisher_id, title, published, first_published, isbn, created_at FROM book
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE book
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE book (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, publisher_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, published INTEGER DEFAULT NULL, first_published INTEGER DEFAULT NULL, isbn VARCHAR(13) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CONSTRAINT FK_CBE5A33140C86FCE FOREIGN KEY (publisher_id) REFERENCES publisher (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO book (id, publisher_id, title, published, first_published, isbn, created_at) SELECT id, publisher_id, title, published, first_published, isbn, created_at FROM __temp__book
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__book
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CBE5A33140C86FCE ON book (publisher_id)
        SQL);
    }
}
