<?php declare(strict_types=1);

namespace App\Mapper;

use App\Entity\Book;
use App\Entity\Tag;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;

class BookMapper extends PinakesMapper {

    public static function getTableName(): string { 
        return 'book';
    }

    public static function getSearchKey(): string { 
        return 'title';
    }

    public static function getEntityClass(): string {
        return Book::class;
    }
    
    public static function getDefaultOrder(): array {
        return [ 'book.created_at' => 'DESC', 'book.title' => 'ASC' ];
    }

    protected function defineDataFields(): array {
        return [
            'title' => [
                'caption' => 'Title',
                'data' => 'title',
                'link' => DataColumn::LINK_SELF,
                'edit' => true
            ],
            'authors' => [
                'caption' => 'Author(s)',
                'data' => 'authors',
                'link' => DataColumn::LINK_DATA,
                'edit' => true
            ],
            'translators' => [
                'caption' => 'Translator(s)',
                'data' => 'translators',
                'link' => DataColumn::LINK_DATA,
                'edit' => true
            ],
            'publisher' => [
                'caption' => 'Publisher',
                'data' => 'publisher',
                'link' => DataColumn::LINK_DATA,
                'edit' => true
            ],
            'tags' => [
                'caption' => 'Tags',
                'data' => 'tags',
                'data_type' => DataType::tags(Tag::class),
                'edit' => true
            ],
            'published' => [
                'caption' => 'Year Published',
                'data' => 'published',
                'edit' => true
            ],
            'first_published' => [
                'caption' => 'First Published',
                'data' => 'first_published',
                'edit' => true
            ],
            'isbn' => [
                'caption' => 'ISBN',
                'data' => 'isbn',
                'edit' => true
            ],
            'series' => [
                'caption' => 'Series',
                'data' => 'series',
                'link' => DataColumn::LINK_DATA,
                'edit' => true
            ],
            'series_volume' => [
                'caption' => 'Vol.',
                'data' => 'series_volume',
                'edit' => true
            ],
            'created_at' => [
                'caption' => 'Created at',
                'data' => 'created_at',
            ]
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([
            'title', 'authors', 'publisher', 'tags', 'published', 'first_published', 'isbn', 'series', 'series_volume'
        ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([
            'title', 'authors', 'translators', 'publisher', 'published', 'first_published', 'isbn', 'tags', 'series', 'series_volume',
            'created_at'
        ]);
    }
    public function getDataFieldsExport(): array {
        return $this->composeDataFields([
            'created_at', 'title', 'authors', 'publisher', 'tags', 'published', 'first_published', 'isbn', 'series', 'series_volume'
        ]);
    }

    public function getDataFieldsListAuthor(): array {
        return $this->composeDataFields([ 'title', 'publisher', 'first_published', 'tags', 'isbn', 'series', 'series_volume' ]);
    }
    public function getDataFieldsListPublisher(): array {
        return $this->composeDataFields([ 'title', 'authors', 'published', 'tags', 'isbn', 'series', 'series_volume' ]);
    }
    public function getDataFieldsListSeries(): array {
        return $this->composeDataFields([ 'series_volume', 'title', 'authors', 'first_published', 'tags', 'isbn'  ]);
    }

    public function getDataFieldsNewest(): array {
        return $this->composeDataFields([ 'title', 'authors' ]);
    }
}