<?php declare(strict_types=1);

namespace App\Database\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class OneToMany implements MappingAttribute {
    
    public function __construct(
        public readonly string $mapping_type,
        public readonly string $target,
        public readonly ?string $mapped_by = null) {
    }
}
