<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;
use App\Traits\NamedEntityTrait;
use Doctrine\ORM\QueryBuilder;

class TagRepository extends PinakesRepository {
    use NamedEntityTrait;

    protected static function getEntityClass(): string {
        return Tag::class;
    }
    
    protected function getListQuery(): QueryBuilder {
        return parent::getListQuery()
            ->addSelect('(SELECT COUNT(bc.id) FROM App\Entity\Book bc JOIN bc.tags bct WHERE bct = e) AS HIDDEN book_count')
            ->addSelect('b')->leftJoin('e.books', 'b');
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => DataColumn::LINK_SELF,
                'edit' => true,
            ],
            'color' => [
                'caption' => 'Color',
                'data' => 'color',
                'data_type' => DataType::color(),
                'edit' => true,
            ],
            'book_count' => [
                'caption' => 'Books',
                'data' => fn(Tag $t) => $t->books->count(),
                'data_type' => DataType::integer(),
                'order_by' => 'book_count'
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'color', 'book_count' ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name', 'color' ]);
    }
    public function getDataFieldsExport(): array {
        return $this->composeDataFields([ 'name', 'color' ]);
    }
}
