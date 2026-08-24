<?php declare(strict_types=1);

namespace App\Mapper;

use App\Entity\RecordLabel;
use App\Pinakes\DataField;

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
