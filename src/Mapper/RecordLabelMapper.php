<?php

namespace App\Mapper;

use App\Entity\RecordLabel;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;

class RecordLabelMapper extends PinakesMapper {

    public static function getTableName(): string { 
        return 'record_label';
    }

    public static function getSearchKey(): string { 
        return 'name';
    }

    public static function getEntityClass(): string {
        return RecordLabel::class;
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => DataColumn::LINK_SELF,
                'edit' => true
            ],
            'record_count' => [
                'caption' => 'Books',
                'data' => fn(RecordLabel $l) => $l->records->count(),
                'data_type' => DataType::integer(),
                'order_by' => 'record_count'
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
