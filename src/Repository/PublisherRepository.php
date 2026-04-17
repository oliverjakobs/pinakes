<?php

namespace App\Repository;

use App\Entity\Publisher;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;
use App\Traits\NamedEntityTrait;
use Doctrine\ORM\QueryBuilder;

class PublisherRepository extends PinakesRepository {
    use NamedEntityTrait;

    protected static function getEntityClass(): string {
        return Publisher::class;
    }
    
    protected function getListQuery(): QueryBuilder {
        return parent::getListQuery()
            ->addSelect('(SELECT COUNT(bc.id) FROM App\Entity\Book bc WHERE bc.publisher = e) AS HIDDEN book_count')
            ->addSelect('b')->leftJoin('e.books', 'b');
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
                'data' => fn(Publisher $p) => $p->books->count(),
                'data_type' => DataType::integer(),
                'order_by' => 'book_count'
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'book_count' ]);
    }

    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name' ]);
    }
}
