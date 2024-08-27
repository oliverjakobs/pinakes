<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\Persistence\ManagerRegistry;

class BookRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Book::class);
    }

    /** @return Book[] Returns an array of Book objects */
     public function search(?string $search, ?array $orderBy = null): array {
         return $this->findLike('title', $search, $orderBy);
     }

    protected function defineDataFields(): array {
        return [
            'title' => array(
                'caption' => 'Title',
                'data' => 'self',
                'link' => fn(Book $b) => $b->getLinkSelf(),
            ),
            'authors' => array(
                'caption' => 'Author(s)',
                'data' => 'authors',
                'link' => fn(Author $a) => $a->getLinkSelf(),
            ),
            'releaseYear' => array(
                'caption' => 'Release Year',
                'data' => 'releaseYear',
            ),
            'isbn' => array(
                'caption' => 'ISBN',
                'data' => 'isbn',
                'default' => '-'
            ),
        ];
    }

    public function getDataFieldsList(): array {
        return $this->getDataFields(array(
            'title', 'authors', 'releaseYear', 'isbn'
        ));
    }
    
    public function getDataFieldsShow(): array {
        return $this->getDataFields(array(
            'authors', 'releaseYear', 'isbn'
        ));
    }
}
