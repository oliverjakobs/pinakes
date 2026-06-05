<?php

namespace App\Repository;

use App\Entity\Artist;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;
use App\Traits\NamedEntityTrait;
use Doctrine\ORM\QueryBuilder;

class ArtistRepository extends PinakesRepository {
    use NamedEntityTrait;

    protected static function getEntityClass(): string {
        return Artist::class;
    }

    protected function getListQuery(): QueryBuilder {
        return parent::getListQuery()
            ->addSelect('(SELECT COUNT(ac.id) FROM App\Entity\Record ac WHERE ac.artist = e) AS HIDDEN record_count')
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
                'data' => fn(Artist $a) => $a->records->count(),
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
