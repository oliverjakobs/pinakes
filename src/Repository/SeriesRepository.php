<?php

namespace App\Repository;

use App\Entity\Series;
use App\Entity\Author;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;
use Doctrine\ORM\QueryBuilder;

class SeriesRepository extends PinakesRepository {

    protected static function getEntityClass(): string {
        return Series::class;
    }

    public function getSearchKey(): string {
        return 'name';
    }

    protected function getListQuery(): QueryBuilder {
        return parent::getListQuery()->addSelect('v')->leftJoin('e.volumes', 'v');
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => DataColumn::LINK_SELF,
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
