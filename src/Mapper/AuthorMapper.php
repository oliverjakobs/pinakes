<?php declare(strict_types=1);

namespace App\Mapper;

use App\Entity\Author;
use App\Pinakes\DataField;

class AuthorMapper extends PinakesMapper {

    public static function getTableName(): string { 
        return 'author';
    }

    public static function getSearchKey(): string { 
        return 'name';
    }

    public static function getEntityClass(): string {
        return Author::class;
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => DataField::LINK_SELF,
                'edit' => true
            ],
            'book_count' => [
                'caption' => 'Books',
                'data' => 'book_count',
            ],
            'translation_count' => [
                'caption' => 'Translations',
                'data' => 'translation_count',
            ],
            'openlibrary' => [
                'caption' => 'OpenLibrary',
                'data' => 'openlibrary',
                'edit' => true
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'book_count', 'translation_count' ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name', 'openlibrary' ]);
    }
    public function getDataFieldsExport(): array {
        return $this->composeDataFields([ 'name', 'openlibrary' ]);
    }
}