<?php declare(strict_types=1);

namespace App\Database\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToOne implements MappingAttribute {
    
    public function __construct(
        public readonly string $target,
        public readonly ?string $inversed_by = null) {
    }
}
