<?php

namespace App\Repository;

use App\Entity\RecordLabel;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;
use App\Traits\NamedEntityTrait;
use Doctrine\ORM\QueryBuilder;

class RecordLabelRepository extends PinakesRepository {
    use NamedEntityTrait;

    protected static function getEntityClass(): string {
        return RecordLabel::class;
    }
    
    protected function getListQuery(): QueryBuilder {
        return parent::getListQuery()
            ->addSelect('(SELECT COUNT(ac.id) FROM App\Entity\Record ac WHERE ac.label = e) AS HIDDEN record_count')
            ->addSelect('a')->leftJoin('e.records', 'a');
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => DataColumn::LINK_SELF,
                'edit' => true
            ],
            'record_count' => [
                'caption' => 'Books',
                'data' => fn(RecordLabel $l) => $l->records->count(),
                'data_type' => DataType::integer(),
                'order_by' => 'record_count'
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'record_count' ]);
    }

    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name' ]);
    }
}
