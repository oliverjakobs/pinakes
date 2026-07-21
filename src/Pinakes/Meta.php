<?php declare(strict_types=1);

namespace App\Pinakes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Meta {

    public readonly string $entity_name;
    
    public function __construct(string $entity_name) {
        $this->entity_name = $entity_name;
    }
}
