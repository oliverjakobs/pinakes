<?php declare(strict_types=1);

namespace App\Mapper;

use App\Entity\Tag;
use App\Pinakes\DataField;
use App\Pinakes\DataType;

class TagMapper extends PinakesMapper {

    public static function getTableName(): string { 
        return 'tag';
    }

    public static function getSearchKey(): string { 
        return 'name';
    }

    public static function getEntityClass(): string {
        return Tag::class;
    }
    
    public static function getDefaultSelect(): array {
        $result = parent::getDefaultSelect();
        $result[] = 'color';
        return $result;
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => DataField::LINK_SELF,
                'data_type' => DataType::string(),
                'edit' => true,
            ],
            'color' => [
                'caption' => 'Color',
                'data' => 'color',
                'data_type' => DataType::color(),
                'edit' => true,
            ],
            'books' => [
                'caption' => 'Books',
                'data' => 'books',
            ],
            'book_count' => [
                'caption' => 'Books',
                'data' => 'book_count',
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'color', 'book_count' ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name', 'color' ]);
    }
    public function getDataFieldsExport(): array {
        return $this->composeDataFields([ 'name', 'color' ]);
    }
}