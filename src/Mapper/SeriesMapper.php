<?php declare(strict_types=1);

namespace App\Mapper;

use App\Entity\Author;
use App\Entity\Series;
use App\Pinakes\DataType;
use App\Pinakes\Singleton;

class SeriesMapper extends PinakesMapper {
    use Singleton;

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
                'link' => true,
                'edit' => true
            ],
            'authors' => [
                'caption' => 'Author(s)',
                'data' => 'authors',
                'data_type' => DataType::collection(Author::class),
                'link' => true
            ],
            'volumes' => [
                'caption' => 'Volumes',
                'data' => 'volumes',
            ],
            'volume_count' => [
                'caption' => 'Volumes',
                'data' => 'volume_count',
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'name', 'authors', 'volume_count' ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name', 'authors' ]);
    }
    public function getDataFieldsExport(): array {
        return $this->composeDataFields([ 'name' ]);
    }
}