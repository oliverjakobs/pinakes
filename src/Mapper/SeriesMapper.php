<?php declare(strict_types=1);

namespace App\Mapper;

use App\Entity\Author;
use App\Entity\Series;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;

class SeriesMapper extends PinakesMapper {

    public static function getTableName(): string { 
        return 'series';
    }

    public static function getSearchKey(): string { 
        return 'name';
    }

    public static function getEntityClass(): string {
        return Series::class;
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
            'authors' => [
                'caption' => 'Author(s)',
                'data' => fn (Series $s) => $s->getAuthors(),
                'data_type' => DataType::collection(Author::class),
                'link' => DataColumn::LINK_DATA
            ],
            'volume_count' => [
                'caption' => 'Volumes',
                'data' => fn(Series $s) => $s->volumes->count(),
                'data_type' => DataType::integer()
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'authors', 'volume_count' ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name', 'authors' ]);
    }
}