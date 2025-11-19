<?php

namespace App\Repository;

use App\Entity\Publisher;
use App\Traits\NamedEntityTrait;
use Doctrine\Persistence\ManagerRegistry;

class PublisherRepository extends PinakesRepository {
    use NamedEntityTrait;

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Publisher::class);
    }

    protected function defineDataFields(): array {
        return [
            'name' => array(
                'caption' => 'Name',
                'data' => 'name',
                'link' => self::LINK_SELF
            ),
            'book_count' => array(
                'caption' => 'Books',
                'data' => fn(Publisher $p) => $p->books->count(),
            ),
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields(array(
            'name', 'book_count'
        ));
    }

    public function getDataFieldsShow(): array {
        return $this->composeDataFields(array(
            'name'
        ));
    }
}
