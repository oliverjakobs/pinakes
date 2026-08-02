<?php declare(strict_types=1);

namespace App\Database\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToMany implements MappingAttribute {
    
    public function __construct(
        public readonly string $target,
        public readonly ?string $mapped_by = null,
        public readonly ?string $inversed_by = null,
        public readonly ?string $join_table = null) {
    }
}
