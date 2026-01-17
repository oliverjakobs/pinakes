<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Author;
use App\Pinakes\Renderer;
use App\Pinakes\EntityCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class BookRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Book::class);
    }

    public function getSearchKey(): string{
        return 'title';
    }

    public function getTemplate(): Book {
        $result = new Book();
        $result->title = 'New Book';
        $result->created_at = new \DateTime();
        return $result;
    }

    protected function getQueryBuilder(array $filter = []): QueryBuilder {
        $qb = parent::getQueryBuilder($filter)->addSelect('a')->leftJoin('e.authors', 'a');

        $this->applyAnd($qb, $filter['author'] ?? [], 'MEMBER OF', 'authors');
        $this->applyAnd($qb, $filter['publisher'] ?? [], '=', 'publisher');
        $this->applyAnd($qb, $filter['tag'] ?? [], 'MEMBER OF', 'tags');
        $this->applyAnd($qb, $filter['ntag'] ?? [], 'NOT MEMBER OF', 'tags');
        $this->applyAnd($qb, $filter['series'] ?? [], '=', 'series');

        return $qb;
    }

    public function getNewest(): EntityCollection {
        $qb = $this->createQueryBuilder('b')->orderBy('b.created_at', 'DESC')->setMaxResults(5);
        return new EntityCollection(Author::class, $qb->getQuery()->getResult());
    }

    protected function defineDataFields(): array {
        return [
            'title' => [
                'caption' => 'Title',
                'data' => 'title',
                'link' => self::LINK_SELF
            ],
            'authors_inline' => [
                'caption' => 'Author(s)',
                'data' => 'authors',
                'render' => fn ($data) => Renderer::RenderCollectionInline($data, '; '),
                'link' => self::LINK_DATA,
                'edit' => false
            ],
            'authors' => [
                'caption' => 'Author(s)',
                'data' => 'authors',
                'link' => self::LINK_DATA,
            ],
            'translators' => [
                'caption' => 'Translator(s)',
                'data' => 'translators',
                'link' => self::LINK_DATA,
            ],
            'publisher' => [
                'caption' => 'Publisher',
                'data' => 'publisher',
                'link' => self::LINK_DATA,
            ],
            'published' => [
                'caption' => 'Year Published',
                'data' => 'published',
                'style_class' => 'align-right fit-content'
            ],
            'first_published' => [
                'caption' => 'First Published',
                'data' => 'first_published',
                'style_class' => 'align-right fit-content'
            ],
            'isbn' => [
                'caption' => 'ISBN',
                'data' => 'isbn',
            ],
            'series' => [
                'caption' => 'Series',
                'data' => 'series',
                'link' => self::LINK_DATA
            ],
            'series_volume' => [
                'caption' => 'Vol.',
                'data' => 'series_volume',
                'style_class' => 'align-right'
            ],
            'tags' => [
                'caption' => 'Tags',
                'data' => fn (Book $b) => $b->getTags(),
                'render' => fn ($data) => Renderer::RenderCollectionInline($data),
                'edit' => 'tags'
            ],
            'tags_export' => [
                'caption' => 'Tags',
                'data' => 'tags',
                'render' => fn ($data) => Renderer::RenderCollectionInline($data, '; '),
            ],
            'created_at' => [
                'caption' => 'Created at',
                'data' => 'created_at',
                'edit' => false

            ]
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([
            'title', 'authors_inline', 'publisher', 'tags', 'published', 'first_published', 'isbn', 'series', 'series_volume'
        ]);
    }

    public function getDataFieldsListAuthor(): array {
        return $this->composeDataFields([ 'title', 'publisher', 'first_published', 'isbn', 'series', 'series_volume' ]);
    }
    public function getDataFieldsListPublisher(): array {
        return $this->composeDataFields([ 'title', 'authors_inline', 'published', 'isbn', 'series', 'series_volume' ]);
    }
    public function getDataFieldsListSeries(): array {
        return $this->composeDataFields([ 'series_volume', 'title', 'authors_inline', 'first_published', 'tags', 'isbn'  ]);
    }

    public function getDataFieldsNewest(): array {
        return $this->composeDataFields([ 'title', 'authors_inline' ]);
    }

    public function getDataFieldsShow(): array {
        return $this->composeDataFields([
            'title', 'authors', 'translators', 'publisher', 'published', 'first_published', 'isbn', 'tags', 'series', 'series_volume', 'created_at'
        ]);
    }

    public function getDataFieldsExport(): array {
        return $this->composeDataFields([
            'created_at', 'title', 'authors_inline', 'publisher', 'tags_export', 'published', 'first_published', 'isbn', 'series', 'series_volume'
        ]);
    }
}
