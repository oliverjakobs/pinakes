<?php

namespace App\Repository;

use App\Entity\PinakesEntity;
use App\Entity\Publisher;
use Doctrine\Persistence\ManagerRegistry;

class PublisherRepository extends PinakesRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Publisher::class);
    }

    public function getOrCreate(string $name, bool $flush = true): Publisher {
        $publisher = $this->findOneBy(['name' => $name]);
        if (null === $publisher) {
            $publisher = new Publisher();
            $publisher->name = $name;
            $this->save($publisher, $flush);
        }

        return $publisher;
    }

    public function getSearchKey(): string{
        return 'name';
    }

    protected function defineDataFields(): array {
        return [
            'name' => array(
                'caption' => 'Name',
                'data' => 'name',
                'link' => self::LINK_SELF
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
            'name'
        ));
    }
}
