<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class Tag extends PinakesEntity {
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
}
