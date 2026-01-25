<?php

namespace App\Repository;

use App\Entity\BoardgamePublisher;
use App\Traits\NamedEntityTrait;

class BoardgamePublisherRepository extends PinakesRepository {
    use NamedEntityTrait;

    protected static function getEntityClass(): string {
        return BoardgamePublisher::class;
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => self::LINK_SELF
            ],
            'boardgame_count' => [
                'caption' => 'Boardgames',
                'data' => fn(BoardgamePublisher $p) => $p->boardgames->count(),
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'boardgame_count' ]);
    }

    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name' ]);
    }
}
