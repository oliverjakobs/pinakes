<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class Series extends PinakesEntity {
    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public string $name = 'Untitled Series';

    /** @var Book[] */
    #[Mapping\OneToMany(target: Book::class, mapped_by: 'series')]
    public array $volumes = [];

    #[Mapping\Count(property: 'volumes')]
    public int $volume_count = 0;

    #[Mapping\Reference(property: 'volumes', reference: 'authors')]
    public array $authors = [];

    public function __toString(): string {
        return $this->name;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getAuthors(): array {
        return [];
    }
}
