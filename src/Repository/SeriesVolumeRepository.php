<?php

namespace App\Repository;

use App\Entity\SeriesVolume;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class SeriesVolumeRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, SeriesVolume::class);
    }

    public function getSearchKey(): string{
        return '';
    }

    protected function getQueryBuilder(array $filter): QueryBuilder {
        $qb = parent::getQueryBuilder($filter);

        if (!empty($filter['series'])) {
            $qb->andWhere($qb->expr()->eq(':series', 'e.series'));
            $qb->setParameter('series', $filter['series']);
        }

        return $qb;
    }

    protected function defineDataFields(): array {
        return [
            'volume' => array(
                'caption' => '#',
                'data' => 'volume',
                'style_class' => 'align-right fit-content'
            ),
            'book' => array(
                'caption' => 'Book',
                'data' => 'book',
                'link' => self::LINK_DATA
            ),
            'publisher' => array(
                'caption' => 'Publisher',
                'data' => fn(SeriesVolume $sv) => $sv->book->publisher,
                'link' => self::LINK_DATA
            ),
            'isbn' => array(
                'caption' => 'ISBN',
                'data' => fn(SeriesVolume $sv) => $sv->book->isbn,
            )
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields(array(
            'volume', 'book', 'publisher', 'isbn'
        ));
    }
}
