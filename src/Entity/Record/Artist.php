<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class Artist extends PinakesEntity {
    
    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public ?string $name = null;

    /** @var Record[] */
    #[Mapping\OneToMany(target: Record::class, mapped_by: 'artist')]
    public array $records = [];

    #[Mapping\Count(property: 'records')]
    public ?int $record_count = null;

    public function __toString(): string {
        return $this->name ?? 'Unknown artist';
    }

    public function getId(): ?int {
        return $this->id;
    }
}
