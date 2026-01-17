<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Traits\NamedEntityTrait;
use App\Pinakes\ViewElement;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends PinakesRepository {
    use NamedEntityTrait;

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Tag::class);
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
                'render' => fn($data) => ViewElement::tag($data, $data)->addClasses(['monospace'])->getHtml(),
                'input_type' => 'color',
            ],
            'book_count' => [
                'caption' => 'Books',
                'data' => fn(Tag $t) => $t->books->count(),
                'style_class' => 'align-right fit-content'
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
