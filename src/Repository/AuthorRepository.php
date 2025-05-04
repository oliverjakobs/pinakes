<?php

namespace App\Repository;

use App\Entity\Author;
use App\Entity\PinakesEntity;
use Doctrine\Persistence\ManagerRegistry;

class AuthorRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Author::class);
    }

    public function getOrCreate(string $name): Author {
        $author = $this->findLike('name', $name);
        if (!empty($author)) return $author[0];

        $author = new Author();
        $author->setName($name);
        $this->save($author);

        return $author;
    }

    /** @return Author[] Returns an array of Author objects */
     public function search(?string $search, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array {
         return $this->findLike('name', $search, $orderBy, $limit, $offset);
     }

    protected function defineDataFields(): array {
        return [
            'name' => array(
                'caption' => 'Name',
                'data' => 'name',
                'link' => self::LINK_SELF
            ),
            'book_list' => array(
                'caption' => 'Books',
                'data' => fn(Author $a) => PinakesEntity::toHtmlList($a->getBooks(), true),
            ),
            'book_count' => array(
                'caption' => 'Books',
                'data' => fn(Author $a) => $a->getBooks()->count(),
            ),
            'openlibrary' => array(
                'caption' => 'OpenLibrary',
                'data' => fn(Author $a) => $a->getLinkOpenLibrary(),
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
            'book_list', 'openlibrary'
        ));
    }
}
