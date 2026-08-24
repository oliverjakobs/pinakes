<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class RecordLabel extends PinakesEntity {
    
    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public ?string $name = null;

    /** @var Record[] */
    #[Mapping\OneToMany(target: Record::class, mapped_by: 'label')]
    public array $records = [];

    #[Mapping\Count(property: 'records')]
    public ?int $record_count = null;

    public function __toString(): string {
        return $this->name ?? 'Unknown label';
    }

    public function getId(): ?int {
        return $this->id;
    }
}
