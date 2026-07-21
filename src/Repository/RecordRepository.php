<?php

namespace App\Repository;

use App\Entity\Record;
use App\Pinakes\DataColumn;

class RecordRepository extends PinakesRepository {

    protected static function getEntityClass(): string {
        return Record::class;
    }

    public function getSearchKey(): string {
        return 'title';
    }

    protected function defineDataFields(): array {
        return [
            'title' => [
                'caption' => 'Title',
                'data' => 'title',
                'link' => DataColumn::LINK_SELF,
                'edit' => true
            ],
            'artist' => [
                'caption' => 'Artist',
                'data' => 'artist',
                'link' => DataColumn::LINK_DATA,
                'edit' => true
            ],
            'label' => [
                'caption' => 'Label',
                'data' => 'label',
                'link' => DataColumn::LINK_DATA,
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
        return $this->composeDataFields([ 'title', 'artist', 'label' ]);
    }
    
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'title', 'artist', 'label', 'released', 'medium' ]);
    }
}
