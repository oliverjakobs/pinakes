<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class Tag extends PinakesEntity implements TagInterface {
    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public string $name = 'Unknown tag';

    #[Mapping\Column]
    public string $color = '#ffffff';

    /** @var Book[] */
    #[Mapping\ManyToMany(target: Book::class, mapped_by: 'tags')]
    public array $books = [];

    #[Mapping\Count(property: 'books')]
    public int $book_count = 0;

    public function __toString(): string {
        return $this->name;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getColor(): string {
        return $this->color;
    }
}
