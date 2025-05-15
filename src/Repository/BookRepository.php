<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use App\Entity\PinakesEntity;
use Doctrine\Persistence\ManagerRegistry;

class BookRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Book::class);
    }

    /** @return Book[] Returns an array of Book objects */
     public function search(?string $search, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array {
         return $this->findLike('title', $search, $orderBy, $limit, $offset);
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
                'data' => fn(Book $b) => $b->getLinksAuthors(),
            ),
            'author_list' => array(
                'caption' => 'Author(s)',
                'data' => 'authors',
                'link' => self::LINK_DATA,
            ),
            'publisher' => array(
                'caption' => 'Publisher',
                'data' => 'publisher',
                'link' => self::LINK_DATA,
            ),
            'published' => array(
                'caption' => 'Year Published',
                'data' => 'published',
            ),
            'first_published' => array(
                'caption' => 'First Published',
                'data' => 'first_published',
            ),
            'isbn' => array(
                'caption' => 'ISBN',
                'data' => 'isbn',
            )
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields(array(
            'title', 'authors', 'publisher', 'published', 'first_published', 'isbn'
        ));
    }
    public function getDataFieldsListAuthor(): array {
        return $this->composeDataFields(array(
            'title', 'publisher', 'first_published', 'isbn'
        ));
    }
    public function getDataFieldsListPublisher(): array {
        return $this->composeDataFields(array(
            'title', 'authors', 'published', 'isbn'
        ));
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields(array(
            'title', 'author_list', 'publisher', 'published', 'first_published', 'isbn'
        ));
    }
}
