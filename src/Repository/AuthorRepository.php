<?php

namespace App\Repository;

use App\Entity\Author;
use App\Entity\Series;
use App\Entity\PinakesEntity;
use App\Pinakes\EntityCollection;
use Doctrine\Persistence\ManagerRegistry;

class AuthorRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Author::class);
    }

    public function getSearchKey(): string{
        return 'name';
    }

    public function getDefaultOrder(): array {
        return [ 'name' => 'ASC' ];
    }

    public function getOrCreate(string $name, bool $flush = true): Author {
        $author = $this->findOneBy(['name' => $name]);
        if (null === $author) {
            $author = new Author();
            $author->name = $name;
            $this->save($author, $flush);
        }

        return $author;
    }

    public function findBySeries(Series $series): EntityCollection {
        if (0 === $series->volumes->count()) return new EntityCollection(Author::class, []);

        $qb = $this->getQueryBuilder([]);
        foreach ($series->volumes as $idx => $book) {
            $qb->orWhere('?' . $idx . ' MEMBER OF e.books');
            $qb->setParameter($idx, $book);
        }
        return new EntityCollection(Author::class, $qb->getQuery()->getResult());
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
                'data' => fn(Author $a) => $a->books->count(),
            ),
            'openlibrary' => array(
                'caption' => 'OpenLibrary',
                'data' => fn(Author $a) => $a->getLinkOpenLibrary(),
                'edit' => 'openlibrary'
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
