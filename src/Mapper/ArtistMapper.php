<?php declare(strict_types=1);

namespace App\Mapper;

use App\Entity\Artist;
use App\Pinakes\DataField;

class ArtistMapper extends PinakesMapper {

    public static function getTableName(): string { 
        return 'artist';
    }

    public static function getSearchKey(): string { 
        return 'name';
    }

    public static function getEntityClass(): string {
        return Artist::class;
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => DataField::LINK_SELF,
                'edit' => true
            ],
            'record_count' => [
                'caption' => 'Records',
                'data' => 'record_count',
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'record_count' ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name' ]);
    }
    public function getDataFieldsExport(): array {
        return $this->composeDataFields([ 'name' ]);
    }
}
