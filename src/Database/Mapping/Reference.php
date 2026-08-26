<?php declare(strict_types=1);

namespace App\Database\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Reference implements MappingAttribute {
    public function __construct(
        public readonly string $property,
        public readonly string $reference) {

    }
}