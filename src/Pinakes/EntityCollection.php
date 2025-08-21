<?php declare(strict_types=1);

namespace App\Pinakes;

use Doctrine\Common\Collections\ArrayCollection;

final class EntityCollection extends ArrayCollection {

    private string $type_class;

    public function __construct(string $type_class, array $elements = []) {
        parent::__construct($elements);
        $this->type_class = $type_class;
    }

    public function getTypeClass(): string {
        return $this->type_class;
    }
}
