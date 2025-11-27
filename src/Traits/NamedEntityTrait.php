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

    public function getTemplate(): PinakesEntity {
        $entity_name = $this->getEntityName();
        $result = new $entity_name();
        $result->name = 'New ' . $entity_name;

        return $result;
    }

    public function getOrCreate(string $name, bool $flush = true): PinakesEntity {
        $result = $this->findOneByName($name);
        if (null === $result) {
            $result = $this->getTemplate();
            $result->name = $name;
            $this->save($result, $flush);
        }

        return $result;
    }
}
