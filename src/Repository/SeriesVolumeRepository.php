<?php

namespace App\Repository;

use App\Entity\SeriesVolume;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SeriesVolumeRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, SeriesVolume::class);
    }

    public function getSearchKey(): string{
        return '';
    }

    protected function defineDataFields(): array {
        return [];
    }
}
