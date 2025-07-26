<?php declare(strict_types=1);

namespace App\Pinakes;

use App\Entity\PinakesEntity;
use Doctrine\ORM\EntityManagerInterface;

class DataTypeEntity extends DataType {
    public string $entity;

    public function __construct(string $entity) {
        $this->entity = $entity;
    }
}
