<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class RecordLabel extends PinakesEntity {
    #[Mapping\Column]
    public string $name = 'Unknown label';

    /** @var Record[] */
    #[Mapping\OneToMany(target: Record::class, mapped_by: 'label')]
    public array $records = [];

    #[Mapping\Count(property: 'records')]
    public int $record_count = 0;

    public function __toString(): string {
        return $this->name;
    }
}
