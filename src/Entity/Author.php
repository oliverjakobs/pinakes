<?php

namespace App\Entity;

use App\Database\Mapping;
use App\Pinakes\Helper;
use App\Renderable\Link;

class Author extends PinakesEntity {
    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public ?string $name = null;

    /** @var Book[] */
    #[Mapping\ManyToMany(target: Book::class, mappedBy: 'authors')]
    public array $books = [];

    /** @var Book[] */
    #[Mapping\ManyToMany(target: Book::class, mappedBy: 'translators', join_table: 'book_translator')]
    public array $translations = [];

    #[Mapping\Column]
    public ?string $openlibrary = null;

    #[Mapping\Count(property: 'books')]
    public ?int $book_count = null;

    #[Mapping\Count(property: 'translations')]
    public ?int $translation_count = null;

    public function __construct() {
    }

    public function __toString(): string {
        return $this->name ?? 'Unknown author';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getLinkOpenLibrary(): ?Link {
        if (null === $this->openlibrary) return null;
        return Link::extern($this->openlibrary, 'https://openlibrary.org/authors/' . $this->openlibrary);
    }

    public function getMessageDelete(): string {
        if (!Helper::isEmpty($this->books)) return 'Author still has books assigned.';
        if (!Helper::isEmpty($this->translations)) return 'Author still has translations assigned.';
        return '';
    }
}
