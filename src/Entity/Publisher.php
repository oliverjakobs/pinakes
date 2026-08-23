<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class Publisher extends PinakesEntity {
    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public ?string $name = null;

    /** @var Book[] */
    #[Mapping\OneToMany(target: Book::class, mappedBy: 'publisher')]
    public array $books;

    #[Mapping\Count(property: 'books')]
    public ?int $book_count = null;

    public function __construct() {
    }

    public function __toString(): string {
        return $this->name ?? 'Unknown publisher';
    }

    public function getId(): ?int {
        return $this->id;
    }
}
