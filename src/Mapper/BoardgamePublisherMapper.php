<?php declare(strict_types=1);

namespace App\Mapper;

use App\Entity\BoardgamePublisher;
use App\Mapper\PinakesMapper;
use App\Pinakes\DataField;
use App\Pinakes\Singleton;

class BoardgamePublisherMapper extends PinakesMapper {
    use Singleton;

    public static function getTableName(): string { 
        return 'boardgame_publisher';
    }

    public static function getSearchKey(): string { 
        return 'name';
    }

    public static function getEntityClass(): string {
        return BoardgamePublisher::class;
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => DataField::LINK_SELF,
                'edit' => true
            ],
            'boardgame_count' => [
                'caption' => 'Boardgames',
                'data' => 'boardgame_count',
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'boardgame_count' ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name' ]);
    }
    public function getDataFieldsExport(): array {
        return $this->composeDataFields([ 'name' ]);
    }
}
