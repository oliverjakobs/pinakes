<?php

namespace App\Repository;

use App\Entity\Boardgame;
use App\Pinakes\FormElement;
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

    protected function defineFilters(): array {
        return [
            'player_count' => [
                'caption' => 'Playercount',
                'form' => FormElement::number(null, 1, 16),
                'filter' => function (QueryBuilder $qb, $filter): QueryBuilder {
                    $qb->andWhere(':player_count <= e.max_player');
                    $qb->andWhere(':player_count >= e.min_player');
                    return $qb->setParameter('player_count', $filter);
                }
            ]
        ];
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => self::LINK_SELF
            ],
            'publisher' => [
                'caption' => 'Publisher',
                'data' => 'publisher',
                'link' => self::LINK_DATA
            ],
            'player_count' => [
                'caption' => 'Players',
                'data' => fn(Boardgame $bg) => $bg->getPlayerCount(),
            ],
            'min_player' => [
                'caption' => 'Players (min)',
                'data' => 'min_player',
            ],
            'max_player' => [
                'caption' => 'Players (max)',
                'data' => 'max_player',
            ],
            'base_game' => [
                'caption' => 'Base game',
                'data' => 'base_game',
                'link' => self::LINK_DATA
            ],
            'extensions' => [
                'caption' => 'Extensions',
                'data' => 'extensions',
                'link' => self::LINK_DATA,
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
}
