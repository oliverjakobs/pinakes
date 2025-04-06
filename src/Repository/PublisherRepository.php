<?php

namespace App\Repository;

use App\Entity\Publisher;
use Doctrine\Persistence\ManagerRegistry;

class PublisherRepository extends PinakesRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Publisher::class);
    }

    /** @return Publisher[] Returns an array of Publisher objects */
     public function search(?string $search, ?array $orderBy = null): array {
         return $this->findLike('name', $search, $orderBy);
     }

     protected function defineDataFields(): array {
        return [
            'name' => array(
                'caption' => 'Name',
                'data' => 'self',
                'link' => fn(Publisher $p) => $p->getLinkSelf(),
            )
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields(array(
            'name'
        ));
    }
}
