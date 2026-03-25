<?php

namespace App\Repository;

use App\Entity\Publisher;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;
use App\Traits\NamedEntityTrait;

class PublisherRepository extends PinakesRepository {
    use NamedEntityTrait;

    protected static function getEntityClass(): string {
        return Publisher::class;
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
                'data_type' => DataType::integer()
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
