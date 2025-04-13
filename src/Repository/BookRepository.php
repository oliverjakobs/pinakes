<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Author;
use App\Entity\Publisher;
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
                'data' => 'title',
                'link' => self::LINK_SELF
            ),
            'authors' => array(
                'caption' => 'Author(s)',
                'data' => fn(Book $b) => $b->getAuthorLinks(),
            ),
            'publisher' => array(
                'caption' => 'Publisher',
                'data' => 'publisher',
                'link' => self::LINK_DATA
            ),
            'year' => array(
                'caption' => 'Year Published',
                'data' => 'published',
            ),
            'isbn' => array(
                'caption' => 'ISBN',
                'data' => 'isbn',
            )
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields(array(
            'title', 'authors', 'publisher', 'year', 'isbn'
        ));
    }
    public function getDataFieldsListAuthor(): array {
        return $this->composeDataFields(array(
            'title', 'publisher', 'year', 'isbn'
        ));
    }
    public function getDataFieldsListPublisher(): array {
        return $this->composeDataFields(array(
            'title', 'authors', 'year', 'isbn'
        ));
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields(array(
            'authors', 'publisher', 'year', 'isbn'
        ));
    }
}