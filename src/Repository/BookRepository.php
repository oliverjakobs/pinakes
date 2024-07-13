<?php

namespace App\Repository;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\Persistence\ManagerRegistry;

class BookRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Book::class);
    }

   /**
    * @return Book[] Returns an array of Book objects
    */
    public function findLikeTitle(?string $title): array {
        return $this->findLike('title', $title);
    }

    public static function getName(): string {
        return 'books';
    }

    protected function defineDataFields(): array {
        return [
            'title' => array(
                'caption' => 'Title',
                'data' => 'self',
                'link' => fn(Book $b) => self::getLinkSelf($b),
            ),
            'authors' => array(
                'caption' => 'Author(s)',
                'data' => 'authors',
                'link' => fn(Author $a) => AuthorRepository::getLinkSelf($a),
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
}
