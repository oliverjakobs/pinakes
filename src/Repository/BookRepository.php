<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Author;
use App\Entity\Tag;
use App\Pinakes\Database;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;
use App\Pinakes\Pinakes;
use Doctrine\ORM\QueryBuilder;

class BookRepository extends PinakesRepository {

    protected static function getEntityClass(): string {
        return Book::class;
    }

    public function getSearchKey(): string {
        return 'title';
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

    public function getNewest(): array {
        $db = new Database(Pinakes::getParameter('app.db_url'));

        $authors_select = $db->get_select('authors', [ 'id', 'name' ]);

        $rows = $db->query(<<<SQL
            SELECT book.*, $authors_select FROM book 
                LEFT JOIN book_author ON book.id = book_author.book_id LEFT JOIN author authors ON authors.id = book_author.author_id
                ORDER BY book.created_at DESC 
                LIMIT 30;
        SQL);

        $entities = [];
        foreach ($rows as $row) {
            $book = new Book();

            $entities[] = $db->hydrate($book, $row);
        }

        return $entities;

        $qb = $this->createQueryBuilder('b')->orderBy('b.created_at', 'DESC')->setMaxResults(30);
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
            'ntags' => [
                'caption' => 'Exclude Tags',
                'data' => 'tags',
                'data_type' => DataType::tags(Tag::class),
                'filter' => function ($qb, $filter) {
                    foreach ($filter as $idx => $value) {            
                        $key = 'tags' . $idx;
                        $qb->andWhere(':' . $key . ' NOT MEMBER OF e.tags');
                        $qb->setParameter($key, $value);
                    }
                    return $qb;
                }
            ],
            'created_at' => [
                'caption' => 'Created at',
                'data' => 'created_at'
            ]
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([
            'title', 'authors', 'publisher', 'tags', 'published', 'first_published', 'isbn', 'series', 'series_volume'
        ]);
    }

    public function getDataFieldsListAuthor(): array {
        return $this->composeDataFields([ 'title', 'publisher', 'first_published', 'published', 'isbn', 'series', 'series_volume' ]);
    }
    public function getDataFieldsListPublisher(): array {
        return $this->composeDataFields([ 'title', 'authors', 'published', 'isbn', 'series', 'series_volume' ]);
    }
    public function getDataFieldsListSeries(): array {
        return $this->composeDataFields([ 'series_volume', 'title', 'authors', 'first_published', 'tags', 'isbn'  ]);
    }

    public function getDataFieldsNewest(): array {
        return $this->composeDataFields([ 'title', 'authors' ]);
    }

    public function getDataFieldsShow(): array {
        return $this->composeDataFields([
            'title', 'authors', 'translators', 'publisher', 'published', 'first_published', 'isbn', 'tags', 'series', 'series_volume', 'created_at'
        ]);
    }

    public function getDataFieldsExport(): array {
        return $this->composeDataFields([
            'created_at', 'title', 'authors', 'publisher', 'tags', 'published', 'first_published', 'isbn', 'series', 'series_volume'
        ]);
    }
}
