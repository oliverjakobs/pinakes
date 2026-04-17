<?php

namespace App\Repository;

use App\Entity\Boardgame;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;
use App\Traits\NamedEntityTrait;
use Doctrine\ORM\QueryBuilder;

class BoardgameRepository extends PinakesRepository {
    use NamedEntityTrait;
    
    protected static function getEntityClass(): string {
        return Boardgame::class;
    }

    public function getTemplate(): Boardgame {
        $result = new Boardgame();
        $result->name = 'New Boardgame';
        $result->created_at = new \DateTime();
        $result->min_player = 1;
        return $result;
    }

    public function getFilterQuery(array $filter = []): QueryBuilder {
        $qb = parent::getFilterQuery($filter);
        $this->applyAnd($qb, $filter['publisher'] ?? [], '=', 'publisher');
        return $qb;
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => DataColumn::LINK_SELF,
                'edit' => true
            ],
            'publisher' => [
                'caption' => 'Publisher',
                'data' => 'publisher',
                'link' => DataColumn::LINK_DATA,
                'edit' => true
            ],
            'player_count' => [
                'caption' => 'Players',
                'data' => fn(Boardgame $bg) => $bg->getPlayerCount(),
                'data_type' => DataType::integer(1, 16),
                'filter' => function (QueryBuilder $qb, $filter): QueryBuilder {
                    $qb->andWhere(':player_count <= e.max_player');
                    $qb->andWhere(':player_count >= e.min_player');
                    return $qb->setParameter('player_count', $filter);
                },
                'order_by' => [ 'e.min_player', 'e.max_player' ]
            ],
            'min_player' => [
                'caption' => 'Players (min)',
                'data' => 'min_player',
                'edit' => true
            ],
            'max_player' => [
                'caption' => 'Players (max)',
                'data' => 'max_player',
                'edit' => true
            ],
            'base_game' => [
                'caption' => 'Base game',
                'data' => 'base_game',
                'link' => DataColumn::LINK_DATA,
                'edit' => true
            ],
            'extensions' => [
                'caption' => 'Extensions',
                'data' => 'extensions',
                'link' => DataColumn::LINK_DATA,
                'edit' => false
            ]
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'publisher', 'player_count' ]);
    }

    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name', 'base_game', 'extensions', 'publisher', 'min_player', 'max_player' ]);
    }

    public function getDataFieldsFilter(): array {
        return $this->composeDataFields([ 'player_count' ]);
    }
}
