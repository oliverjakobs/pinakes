<?php declare(strict_types=1);

namespace App\Mapper;

use App\Entity\Boardgame;
use App\Pinakes\DataField;
use App\Pinakes\DataType;

class BoardgameMapper extends PinakesMapper {

    public static function getTableName(): string { 
        return 'boardgame';
    }

    public static function getSearchKey(): string { 
        return 'name';
    }

    public static function getEntityClass(): string {
        return Boardgame::class;
    }
    
    public static function getDefaultOrder(): array {
        return [ 'self.created_at' => 'DESC', 'self.name' => 'ASC' ];
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => DataField::LINK_SELF,
                'edit' => true
            ],
            'publisher' => [
                'caption' => 'Publisher',
                'data' => 'publisher',
                'link' => DataField::LINK_DATA,
                'edit' => true
            ],
            'player_count' => [
                'caption' => 'Players',
                'data' => fn(Boardgame $bg) => $bg->getPlayerCount(),
                'data_type' => DataType::integer(1, 16),
                // 'filter' => function (QueryBuilder $qb, $filter): QueryBuilder {
                //     $qb->andWhere(':player_count <= e.max_player');
                //     $qb->andWhere(':player_count >= e.min_player');
                //     return $qb->setParameter('player_count', $filter);
                // },
                // 'order_by' => [ 'e.min_player', 'e.max_player' ]
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
                'link' => DataField::LINK_DATA,
                'edit' => true
            ],
            'extensions' => [
                'caption' => 'Extensions',
                'data' => 'extensions',
                'link' => DataField::LINK_DATA,
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
    public function getDataFieldsExport(): array {
        return $this->composeDataFields([ 'name', 'base_game', 'extensions', 'publisher', 'min_player', 'max_player' ]);
    }

    public function getDataFieldsFilter(): array {
        return $this->composeDataFields([ 'player_count' ]);
    }
}
