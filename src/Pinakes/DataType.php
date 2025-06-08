<?php declare(strict_types=1);

namespace App\Pinakes;

class DataType {
    public string $entity;

    public function __construct(string $entity) {
        $this->entity = $entity;
    }
}
