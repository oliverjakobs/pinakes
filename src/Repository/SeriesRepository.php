<?php

namespace App\Repository;

use App\Entity\Series;
use App\Entity\Author;
use App\Traits\NamedEntityTrait;
use App\Pinakes\Renderer;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class SeriesRepository extends PinakesRepository {
    use NamedEntityTrait;

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Series::class);
    }

    protected function getQueryBuilder(array $filter): QueryBuilder {
        return parent::getQueryBuilder($filter)->addSelect('v')->leftJoin('e.volumes', 'v');
    }

    protected function defineDataFields(): array {
        return [
            'name' => array(
                'caption' => 'Name',
                'data' => 'name',
                'link' => self::LINK_SELF
            ),
            'authors' => array(
                'caption' => 'Author(s)',
                'data' => fn (Series $s) => $this->getEntityManager()->getRepository(Author::class)->findBySeries($s),
                'link' => self::LINK_DATA,
                'edit' => false
            ),
            'authors_inline' => array(
                'caption' => 'Author(s)',
                'data' => fn (Series $s) => $this->getEntityManager()->getRepository(Author::class)->findBySeries($s),
                'render' => fn ($data) => Renderer::RenderCollectionInline($data, '; ', 5),
                'link' => self::LINK_DATA,
            ),
            'volume_count' => array(
                'caption' => 'Volumes',
                'data' => fn(Series $s) => $s->volumes->count(),
                'style_class' => 'align-right'
            ),
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields(array(
            'name', 'authors_inline', 'volume_count'
        ));
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields(array(
            'name', 'authors'
        ));
    }
}
