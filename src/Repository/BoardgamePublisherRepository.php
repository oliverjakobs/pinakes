<?php

namespace App\Repository;

use App\Entity\BoardgamePublisher;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;

class BoardgamePublisherRepository extends PinakesRepository {

    protected static function getEntityClass(): string {
        return BoardgamePublisher::class;
    }

    public function getSearchKey(): string {
        return 'name';
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => DataColumn::LINK_SELF,
                'edit' => true
            ],
            'boardgame_count' => [
                'caption' => 'Boardgames',
                'data' => fn(BoardgamePublisher $p) => $p->boardgames->count(),
                'data_type' => DataType::integer()
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
