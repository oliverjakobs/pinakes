<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class Tag extends PinakesEntity implements TagInterface {
    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public ?string $name = null;

    #[Mapping\Column]
    public ?string $color = null;

    /** @var Book[] */
    #[Mapping\ManyToMany(target: Book::class, mappedBy: 'tags')]
    public array $books;

    #[Mapping\Count(property: 'books')]
    public ?int $book_count = null;

    public function __construct() {
        $this->color = '#ffffff';
    }

    public function __toString(): string {
        return $this->name ?? 'Unknown tag';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getColor(): string {
        return $this->color;
    }
}
