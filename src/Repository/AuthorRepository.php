<?php

namespace App\Repository;

use App\Entity\Author;
use App\Entity\PinakesEntity;
use Doctrine\Persistence\ManagerRegistry;

class AuthorRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Author::class);
    }

    public function getOrCreate(string $name, bool $flush = true): Author {
        $author = $this->findOneBy(['name' => $name]);
        if (null === $author) {
            $author = new Author();
            $author->setName($name);
            $this->save($author, $flush);
        }

        return $author;
    }

    public function getSearchKey(): string{
        return 'name';
    }

    protected function defineDataFields(): array {
        return [
            'name' => array(
                'caption' => 'Name',
                'data' => 'name',
                'link' => self::LINK_SELF
            ),
            'book_count' => array(
                'caption' => 'Books',
                'data' => fn(Author $a) => $a->getBooks()->count(),
            ),
            'openlibrary' => array(
                'caption' => 'OpenLibrary',
                'data' => 'openlibrary',
                'link' => fn(Author $a) => $a->getLinkOpenLibrary(),
            ),
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields(array(
            'name', 'book_count'
        ));
    }
    
    public function getDataFieldsShow(): array {
        return $this->composeDataFields(array(
            'name', 'openlibrary'
        ));
    }
}
