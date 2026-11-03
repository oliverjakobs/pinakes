<?php declare(strict_types=1);

namespace App\Mapper;

use App\Entity\Record;
use App\Pinakes\DataField;
use App\Pinakes\Singleton;

class RecordMapper extends PinakesMapper {
    use Singleton;

    public static function getTableName(): string { 
        return 'record';
    }

    public static function getSearchKey(): string { 
        return 'title';
    }

    public static function getEntityClass(): string {
        return Record::class;
    }

    protected function defineDataFields(): array {
        return [
            'title' => [
                'caption' => 'Title',
                'data' => 'title',
                'link' => DataField::LINK_SELF,
                'edit' => true
            ],
            'artist' => [
                'caption' => 'Artist',
                'data' => 'artist',
                'link' => DataField::LINK_DATA,
                'edit' => true
            ],
            'label' => [
                'caption' => 'Label',
                'data' => 'label',
                'link' => DataField::LINK_DATA,
                'edit' => true
            ],
            'released' => [
                'caption' => 'Released',
                'data' => 'released',
                'edit' => true
            ],
            'medium' => [
                'caption' => 'Medium',
                'data' => 'medium',
                'edit' => true
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'title', 'artist', 'label', 'medium' ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'title', 'artist', 'label', 'released', 'medium' ]);
    }
    public function getDataFieldsExport(): array {
        return $this->composeDataFields([ 'title', 'artist', 'label', 'medium' ]);
    }
}
