<?php

namespace App\Repository;

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

    public function getFields(): array {
        return [
            array(
                'name' => 'title',
                'caption' => 'Title',
                'link' => fn(Book $p) => '/books/' . $p->getId(),
            ),
            array(
                'name' => 'authors',
                'caption' => 'Author(s)',
            ),
            array(
                'name' => 'releaseYear',
                'caption' => 'Release Year',
            ),
            array(
                'name' => 'isbn',
                'caption' => 'ISBN',
                'default' => '-'
            ),
        ];
    }
}
