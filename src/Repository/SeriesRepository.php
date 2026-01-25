<?php

namespace App\Repository;

use App\Entity\Series;
use App\Entity\Author;
use App\Traits\NamedEntityTrait;
use App\Pinakes\Renderer;
use Doctrine\ORM\QueryBuilder;

class SeriesRepository extends PinakesRepository {
    use NamedEntityTrait;

    protected static function getEntityClass(): string {
        return Series::class;
    }

    protected function getQueryBuilder(array $filter = []): QueryBuilder {
        return parent::getQueryBuilder($filter)->addSelect('v')->leftJoin('e.volumes', 'v');
    }

    protected function defineDataFields(): array {
        return [
            'name' => [
                'caption' => 'Name',
                'data' => 'name',
                'link' => self::LINK_SELF
            ],
            'authors' => [
                'caption' => 'Author(s)',
                'data' => fn (Series $s) => $s->getAuthors(),
                'link' => self::LINK_DATA,
                'edit' => false
            ],
            'authors_inline' => [
                'caption' => 'Author(s)',
                'data' => fn (Series $s) => $s->getAuthors(),
                'render' => fn ($data) => Renderer::RenderCollectionInline($data, '; ', 5),
                'link' => self::LINK_DATA,
            ],
            'volume_count' => [
                'caption' => 'Volumes',
                'data' => fn(Series $s) => $s->volumes->count(),
                'style_class' => 'align-right'
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
