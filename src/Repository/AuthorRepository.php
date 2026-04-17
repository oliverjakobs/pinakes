<?php

namespace App\Repository;

use App\Entity\Author;
use App\Entity\Series;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;
use App\Traits\NamedEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;

class AuthorRepository extends PinakesRepository {
    use NamedEntityTrait;

    protected static function getEntityClass(): string {
        return Author::class;
    }
    
    protected function getListQuery(): QueryBuilder {
        return parent::getListQuery()
            ->addSelect('(SELECT COUNT(bc.id) FROM App\Entity\Book bc JOIN bc.authors bca WHERE bca = e) AS HIDDEN book_count')
            ->addSelect('(SELECT COUNT(tc.id) FROM App\Entity\Book tc JOIN tc.translators tct WHERE tct = e) AS HIDDEN translation_count')
            ->addSelect('b, t')->leftJoin('e.books', 'b')->leftJoin('e.translations', 't');
    }

    public function findBySeries(Series $series): Collection {
        if (0 === $series->volumes->count()) return new ArrayCollection();

        $qb = $this->applyOr($this->createQueryBuilder('e'), $series->volumes, 'MEMBER OF', 'books');
        return new ArrayCollection($qb->getQuery()->getResult());
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => DataColumn::LINK_SELF,
                'edit' => true
            ],
            'book_count' => [
                'caption' => 'Books',
                'data' => fn(Author $a) => $a->books->count(),
                'data_type' => DataType::integer(),
                'order_by' => 'book_count'
            ],
            'translation_count' => [
                'caption' => 'Translations',
                'data' => fn(Author $a) => $a->translations->count(),
                'data_type' => DataType::integer(),
                'order_by' => 'translation_count'
            ],
            'openlibrary' => [
                'caption' => 'OpenLibrary',
                'data' => 'openlibrary',
                'edit' => true
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'book_count', 'translation_count' ]);
    }
    
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name', 'openlibrary' ]);
    }
}
