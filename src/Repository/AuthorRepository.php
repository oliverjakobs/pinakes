<?php

namespace App\Repository;

use App\Entity\Author;
use App\Entity\PinakesEntity;
use Doctrine\Persistence\ManagerRegistry;

class AuthorRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Author::class);
    }

    /** @return Author[] Returns an array of Author objects */
     public function search(?string $search, ?array $orderBy = null): array {
         return $this->findLike('name', $search, $orderBy);
     }

    protected function defineDataFields(): array {
        return [
            'name' => array(
                'caption' => 'Name',
                'data' => 'self',
                'link' => fn(Author $a) => $a->getLinkSelf(),
            ),
            'books' => array(
                'caption' => 'Books',
                'data' => fn(Author $a) => PinakesEntity::toHtmlList($a->getBooks(), true),
            ),
            'book_count' => array(
                'caption' => 'Books',
                'data' => fn(Author $a) => $a->getBooks()->count(),
            ),
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields(array(
            'name', 'book_count'
        ));
    }
}