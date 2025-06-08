<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Author;
use App\Entity\PinakesEntity;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class BookRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Book::class);
    }

    public function getSearchKey(): string{
        return 'title';
    }

    protected function getQueryBuilder(array $filter): QueryBuilder {
        $qb = parent::getQueryBuilder($filter)->addSelect('v')->join('e.volume', 'v');

        if (!empty($filter['author'])) {
            $qb->andWhere($qb->expr()->isMemberOf(':author', 'e.authors'));
            $qb->setParameter('author', $filter['author']);
        }

        if (!empty($filter['publisher'])) {
            $qb->andWhere($qb->expr()->eq(':publisher', 'e.publisher'));
            $qb->setParameter('publisher', $filter['publisher']);
        }

        return $qb;
    }

    protected function defineDataFields(): array {
        return [
            'title' => array(
                'caption' => 'Title',
                'data' => 'title',
                'link' => self::LINK_SELF
            ),
            'authors_short' => array(
                'caption' => 'Author(s)',
                'data' => fn(Book $b) => $b->getLinksAuthors(),
            ),
            'authors' => array(
                'caption' => 'Author(s)',
                'data' => 'authors',
                'data_type' => Author::getDataType(),
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
            ),
            'openlibrary' => array(
                'caption' => 'OpenLibrary',
                'data' => fn(Book $b) => $b->getLinkOpenLibrary(),
                'edit' => false
            ),
            'series' => array(
                'caption' => 'Series',
                'data' => fn(Book $b) => $b->getLinkSeries(),
                'edit' => false
            ),
            'volume' => array(
                'caption' => 'Volume',
                'data' => fn(Book $b) => $b->getSeriesVolume(),
                'edit' => false
            ),
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields(array(
            'title', 'authors_short', 'publisher', 'published', 'first_published', 'isbn'
        ));
    }
    public function getDataFieldsListAuthor(): array {
        return $this->composeDataFields(array(
            'title', 'publisher', 'first_published', 'isbn'
        ));
    }
    public function getDataFieldsListPublisher(): array {
        return $this->composeDataFields(array(
            'title', 'authors_short', 'published', 'isbn'
        ));
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields(array(
            'title', 'authors', 'publisher', 'published', 'first_published', 'isbn', 'openlibrary', 'series', 'volume'
        ));
    }
}
