<?php

namespace App\Entity;

use App\Database\Mapping;
use App\Pinakes\Helper;
use App\Renderable\Link;

class Author extends PinakesEntity {
    #[Mapping\Column]
    public string $name = 'Unknown author';

    /** @var Book[] */
    #[Mapping\ManyToMany(target: Book::class, mapped_by: 'authors')]
    public array $books = [];

    /** @var Book[] */
    #[Mapping\ManyToMany(target: Book::class, mapped_by: 'translators', join_table: 'book_translator')]
    public array $translations = [];

    #[Mapping\Column]
    public ?string $openlibrary = null;

    #[Mapping\Count(property: 'books')]
    public int $book_count = 0;

    #[Mapping\Count(property: 'translations')]
    public int $translation_count = 0;

    public function __toString(): string {
        return $this->name;
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
