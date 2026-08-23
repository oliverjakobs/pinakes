<?php declare(strict_types=1);

namespace App\Entity;

use App\Pinakes\Helper;
use App\Database\Mapping;
use App\Renderable\Link;

class Book extends PinakesEntity {
    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public ?string $title = null;

    /** @var Author[] */
    #[Mapping\ManyToMany(target: Author::class, inversed_by: 'books')]
    public array $authors = [];

    /** @var Author[] */
    #[Mapping\ManyToMany(target: Author::class, inversed_by: 'translations', join_table: 'book_translator')]
    public array $translators = [];

    #[Mapping\Column]
    public ?int $published = null;

    #[Mapping\Column]
    public ?int $first_published = null;

    #[Mapping\ManyToOne(target: Publisher::class, inversed_by: 'books')]
    public ?Publisher $publisher = null;

    #[Mapping\Column]
    public ?string $isbn = null;

    #[Mapping\ManyToOne(target: Series::class, inversed_by: 'volumes')]
    public ?Series $series = null;

    #[Mapping\Column]
    public ?int $series_volume = null;

    #[Mapping\Column]
    public \DateTime $created_at;

    /** @var Tag[] */
    #[Mapping\ManyToMany(target: Tag::class, inversed_by: 'books')]
    public array $tags = [];

    public function __construct() {
        $this->created_at = new \DateTime();
    }

    public function __toString(): string {
        return $this->title ?? 'Untitled book';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getLinkOpenLibrary(): ?Link {
        if (Helper::isEmpty($this->isbn)) return null;
        return Link::extern('OpenLibrary', 'https://openlibrary.org/isbn/' . $this->isbn);
    }
}
