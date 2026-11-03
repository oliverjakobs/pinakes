<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class Artist extends PinakesEntity {
    #[Mapping\Column]
    public string $name = 'Unknown artist';

    /** @var Record[] */
    #[Mapping\OneToMany(target: Record::class, mapped_by: 'artist')]
    public array $records = [];

    #[Mapping\Count(property: 'records')]
    public int $record_count = 0;

    public function __toString(): string {
        return $this->name;
    }
}
