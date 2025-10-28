<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Author;
use App\Entity\Genre;
use App\Entity\PinakesEntity;
use App\Pinakes\Context;
use App\Pinakes\EntityCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

use function App\Pinakes\RenderCollectionInline;

class BookRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Book::class);
    }

    public function getSearchKey(): string{
        return 'title';
    }

    protected function getQueryBuilder(array $filter): QueryBuilder {
        $qb = parent::getQueryBuilder($filter)->addSelect('v')->leftJoin('e.volume', 'v');

        if (!empty($filter['author'])) {
            $qb->andWhere($qb->expr()->isMemberOf(':author', 'e.authors'));
            $qb->setParameter('author', $filter['author']);
        }

        if (!empty($filter['publisher'])) {
            $qb->andWhere($qb->expr()->eq(':publisher', 'e.publisher'));
            $qb->setParameter('publisher', $filter['publisher']);
        }

        if (!empty($filter['genre'])) {
            $qb->andWhere($qb->expr()->isMemberOf(':genre', 'e.genre'));
            $qb->setParameter('genre', $filter['genre']);
        }

        return $qb;
    }

    public function getNewest(): EntityCollection {
        $qb = $this->createQueryBuilder('b')->setMaxResults(5);
        return new EntityCollection(Author::class, $qb->getQuery()->getResult());
    }

    protected function defineDataFields(): array {
        return [
            'title' => array(
                'caption' => 'Title',
                'data' => 'title',
                'link' => self::LINK_SELF
            ),
            'authors_inline' => array(
                'caption' => 'Author(s)',
                'data' => 'authors',
                'render' => fn ($data) => RenderCollectionInline($data, '; '),
                'link' => self::LINK_DATA,
            ),
            'authors' => array(
                'caption' => 'Author(s)',
                'data' => 'authors',
                'edit_callback' => function (Book $book, $authors) {
                    $rep = Context::getRepository(Author::class);
                    $book->clearAuthors();
                    foreach ($authors as $author) {
                        if (empty($author)) continue;
                        $book->addAuthor($rep->getOrCreate($author, false));
                    }
                },
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
                'style_class' => 'align-right fit-content'
            ),
            'first_published' => array(
                'caption' => 'First Published',
                'data' => 'first_published',
                'style_class' => 'align-right fit-content'
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
                'data' => 'series',
                'link' => self::LINK_DATA,
                'edit' => false
            ),
            'volume' => array(
                'caption' => 'Volume',
                'data' => fn(Book $b) => $b->getSeriesVolume(),
                'edit' => false
            ),
            'genre' => array(
                'caption' => 'Genre',
                'data' => fn (Book $b) => $b->getGenreTags(),
                'render' => fn ($data) => RenderCollectionInline($data),
                'edit' => 'genre',
                'edit_callback' => function (Book $book, $genre) {
                    $rep = Context::getRepository(Genre::class);
                    $book->clearGenre();
                    foreach ($genre as $name) {
                        if (empty($name)) continue;
                        $book->addGenre($rep->getOrCreate($name, false));
                    }
                },
            ),
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields(array(
            'title', 'authors_inline', 'publisher', 'genre', 'published', 'first_published', 'isbn'
        ));
    }
    public function getDataFieldsNewest(): array {
        return $this->composeDataFields(array(
            'title', 'authors_inline'
        ));
    }
    public function getDataFieldsListAuthor(): array {
        return $this->composeDataFields(array(
            'title', 'publisher', 'first_published', 'isbn'
        ));
    }
    public function getDataFieldsListPublisher(): array {
        return $this->composeDataFields(array(
            'title', 'authors_inline', 'published', 'isbn'
        ));
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields(array(
            'title', 'authors', 'publisher', 'published', 'first_published', 'isbn', 'openlibrary', 'genre', 'series', 'volume'
        ));
    }
}
