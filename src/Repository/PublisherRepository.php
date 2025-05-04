<?php

namespace App\Repository;

use App\Entity\PinakesEntity;
use App\Entity\Publisher;
use Doctrine\Persistence\ManagerRegistry;

class PublisherRepository extends PinakesRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Publisher::class);
    }

    public function getOrCreate(string $name): Publisher {
        $publisher = $this->findLike('name', $name);
        if (!empty($publisher)) return $publisher[0];

        $publisher = new Publisher();
        $publisher->setName($name);
        $this->save($publisher);

        return $publisher;
    }

    /** @return Publisher[] Returns an array of Publisher objects */
     public function search(?string $search, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array {
         return $this->findLike('name', $search, $orderBy, $limit, $offset);
     }

     protected function defineDataFields(): array {
        return [
            'name' => array(
                'caption' => 'Name',
                'data' => 'name',
                'link' => self::LINK_SELF
            ),
            'book_list' => array(
                'caption' => 'Books',
                'data' => fn(Publisher $p) => PinakesEntity::toHtmlList($p->getBooks(), true),
            ),
            'book_count' => array(
                'caption' => 'Books',
                'data' => fn(Publisher $p) => $p->getBooks()->count(),
            ),
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields(array(
            'name', 'book_count'
        ));
    }

    public function getDataFieldsShow(): array {
        return $this->composeDataFields(array(
            'book_list', 'openlibrary'
        ));
    }
}
