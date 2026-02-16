<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Pinakes\DataType;
use App\Traits\NamedEntityTrait;

class TagRepository extends PinakesRepository {
    use NamedEntityTrait;

    protected static function getEntityClass(): string {
        return Tag::class;
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => self::LINK_SELF
            ],
            'color' => [
                'caption' => 'Color',
                'data' => 'color',
                'data_type' => DataType::color(),
            ],
            'book_count' => [
                'caption' => 'Books',
                'data' => fn(Tag $t) => $t->books->count(),
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'color', 'book_count' ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name', 'color' ]);
    }
}
