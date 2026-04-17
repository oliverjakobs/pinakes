<?php

namespace App\Repository;

use App\Entity\Series;
use App\Entity\Author;
use App\Pinakes\DataColumn;
use App\Pinakes\DataType;
use App\Traits\NamedEntityTrait;
use Doctrine\ORM\QueryBuilder;

class SeriesRepository extends PinakesRepository {
    use NamedEntityTrait;

    protected static function getEntityClass(): string {
        return Series::class;
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
            'authors_inline' => [
                'caption' => 'Author(s)',
                'data' => fn (Series $s) => $s->getAuthors(),
                'data_type' => DataType::collection(Author::class, '; '),
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
        return $this->composeDataFields([ 'name', 'authors_inline', 'volume_count' ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'name', 'authors' ]);
    }
}
