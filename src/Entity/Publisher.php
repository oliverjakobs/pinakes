<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class Publisher extends PinakesEntity {
    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public string $name = 'Unknown publisher';

    /** @var Book[] */
    #[Mapping\OneToMany(target: Book::class, mapped_by: 'publisher')]
    public array $books;

    #[Mapping\Count(property: 'books')]
    public int $book_count = 0;

    public function __toString(): string {
        return $this->name;
    }

    public function getId(): ?int {
        return $this->id;
    }
}
