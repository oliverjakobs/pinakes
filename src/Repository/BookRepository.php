<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Author;
use App\Entity\Tag;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;
use Doctrine\ORM\QueryBuilder;

class BookRepository extends PinakesRepository {

    protected static function getEntityClass(): string {
        return Book::class;
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
    
    public function getDefaultOrder(): array {
        return [ 'created_at' => 'DESC', 'title' => 'ASC' ];
    }

    protected function getListQuery(): QueryBuilder {
        return parent::getListQuery()
            ->addSelect('a')->leftJoin('e.authors', 'a')
            ->addSelect('p')->leftJoin('e.publisher', 'p')
            ->addSelect('s')->leftJoin('e.series', 's')
            ->addSelect('t')->leftJoin('e.tags', 't');
    }

    public function getFilterQuery(array $filter): QueryBuilder {
        $qb = parent::getFilterQuery($filter);
        
        $this->applyAnd($qb, $filter['author'] ?? [], 'MEMBER OF', 'authors');
        $this->applyAnd($qb, $filter['publisher'] ?? [], '=', 'publisher');
        $this->applyAnd($qb, $filter['tag'] ?? [], 'MEMBER OF', 'tags');
        $this->applyAnd($qb, $filter['ntag'] ?? [], 'NOT MEMBER OF', 'tags');
        $this->applyAnd($qb, $filter['series'] ?? [], '=', 'series');

        return $qb;
    }

    public function getNewest(): array {
        $qb = $this->createQueryBuilder('b')->orderBy('b.created_at', 'DESC')->setMaxResults(5);
        return $qb->getQuery()->getResult();
    }

    protected function defineDataFields(): array {
        return [
            'title' => [
                'caption' => 'Title',
                'data' => 'title',
                'link' => DataColumn::LINK_SELF,
                'edit' => true
            ],
            'authors_inline' => [
                'caption' => 'Author(s)',
                'data' => 'authors',
                'data_type' => DataType::collection(Author::class, '; '),
                'link' => DataColumn::LINK_DATA
            ],
            'authors' => [
                'caption' => 'Author(s)',
                'data' => 'authors',
                'link' => DataColumn::LINK_DATA,
                'edit' => true
            ],
            'translators' => [
                'caption' => 'Translator(s)',
                'data' => 'translators',
                'link' => DataColumn::LINK_DATA,
                'edit' => true
            ],
            'publisher' => [
                'caption' => 'Publisher',
                'data' => 'publisher',
                'link' => DataColumn::LINK_DATA,
                'edit' => true
            ],
            'published' => [
                'caption' => 'Year Published',
                'data' => 'published',
                'edit' => true
            ],
            'first_published' => [
                'caption' => 'First Published',
                'data' => 'first_published',
                'edit' => true
            ],
            'isbn' => [
                'caption' => 'ISBN',
                'data' => 'isbn',
                'edit' => true
            ],
            'series' => [
                'caption' => 'Series',
                'data' => 'series',
                'link' => DataColumn::LINK_DATA,
                'edit' => true
            ],
            'series_volume' => [
                'caption' => 'Vol.',
                'data' => 'series_volume',
                'edit' => true
            ],
            'tags' => [
                'caption' => 'Tags',
                'data' => 'tags',
                'data_type' => DataType::tags(Tag::class),
                'edit' => true
            ],
            'created_at' => [
                'caption' => 'Created at',
                'data' => 'created_at'
            ]
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([
            'title', 'authors_inline', 'publisher', 'tags', 'published', 'first_published', 'isbn', 'series', 'series_volume'
        ]);
    }

    public function getDataFieldsListAuthor(): array {
        return $this->composeDataFields([ 'title', 'publisher', 'first_published', 'published', 'isbn', 'series', 'series_volume' ]);
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
            'created_at', 'title', 'authors_inline', 'publisher', 'tags', 'published', 'first_published', 'isbn', 'series', 'series_volume'
        ]);
    }
}
