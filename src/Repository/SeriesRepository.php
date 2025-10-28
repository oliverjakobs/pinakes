<?php

namespace App\Repository;

use App\Entity\Author;
use App\Entity\Series;
use App\Entity\SeriesVolume;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function App\Pinakes\RenderCollectionInline;

class SeriesRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Series::class);
    }

    public function getSearchKey(): string{
        return 'name';
    }

    public function getOrCreate(string $name, bool $flush = true): Series {
        $series = $this->findOneBy(['name' => $name]);
        if (null === $series) {
            $series = new Series();
            $series->name = $name;
            $this->save($series, $flush);
        }

        return $series;
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
                'render' => fn ($data) => RenderCollectionInline($data, '; ', 5),
                'link' => self::LINK_DATA,
            ),
            'volume_count' => array(
                'caption' => 'Volumes',
                'data' => fn(Series $s) => $s->volumes->count(),
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
