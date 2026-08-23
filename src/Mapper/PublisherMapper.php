<?php declare(strict_types=1);

namespace App\Mapper;

use App\Entity\Publisher;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;

class PublisherMapper extends PinakesMapper {

    public static function getTableName(): string { 
        return 'publisher';
    }

    public static function getSearchKey(): string { 
        return 'name';
    }

    public static function getEntityClass(): string {
        return Publisher::class;
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => DataColumn::LINK_SELF,
                'data_type' => DataType::string(),
                'edit' => true
            ],
            'book_count' => [
                'caption' => 'Books',
                'data' => 'book_count',
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'book_count' ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name' ]);
    }
    public function getDataFieldsExport(): array {
        return $this->composeDataFields([ 'name' ]);
    }
}