<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230907135801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE author (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE paper (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, release_year INT NOT NULL, doi VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE paper_author (paper_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_19FD39C8E6758861 (paper_id), INDEX IDX_19FD39C8F675F31B (author_id), PRIMARY KEY(paper_id, author_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE paper_author ADD CONSTRAINT FK_19FD39C8E6758861 FOREIGN KEY (paper_id) REFERENCES paper (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE paper_author ADD CONSTRAINT FK_19FD39C8F675F31B FOREIGN KEY (author_id) REFERENCES author (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE paper_author DROP FOREIGN KEY FK_19FD39C8E6758861');
        $this->addSql('ALTER TABLE paper_author DROP FOREIGN KEY FK_19FD39C8F675F31B');
        $this->addSql('DROP TABLE author');
        $this->addSql('DROP TABLE paper');
        $this->addSql('DROP TABLE paper_author');
    }
}
