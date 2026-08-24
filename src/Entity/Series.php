<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class Series extends PinakesEntity {
    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public ?string $name = null;

    /** @var Book[] */
    #[Mapping\OneToMany(target: Book::class, mapped_by: 'series')]
    public array $volumes;

    #[Mapping\Count(property: 'volumes')]
    public ?int $volume_count = null;

    public function __construct() {
    }

    public function __toString(): string {
        return $this->name ?? 'Untitled Series';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getAuthors(): array {
        return [];
    }
}
