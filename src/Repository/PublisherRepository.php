<?php

namespace App\Repository;

use App\Entity\Publisher;
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
                'link' => self::LINK_SELF
            ],
            'book_count' => [
                'caption' => 'Books',
                'data' => fn(Publisher $p) => $p->books->count(),
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
