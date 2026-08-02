<?php declare(strict_types=1);

namespace App\Database\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column implements MappingAttribute {    
    public function __construct() {

    }
}