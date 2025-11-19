<?php declare(strict_types=1);

namespace App\Traits;

use App\Entity\PinakesEntity;

trait NamedEntityTrait {
    public function getSearchKey(): string{
        return 'name';
    }

    public function getDefaultOrder(): array {
        return [ 'name' => 'ASC' ];
    }

    public function findOneByName(string $name): ?PinakesEntity {
        return $this->findOneBy(['name' => $name]);
    }

    public function getOrCreate(string $name, bool $flush = true): PinakesEntity {
        $entity = $this->findOneByName($name);
        if (null === $entity) {
            $entity_name = $this->getEntityName();
            $entity = new $entity_name();
            $entity->name = $name;
            $this->save($entity, $flush);
        }

        return $entity;
    }
}
