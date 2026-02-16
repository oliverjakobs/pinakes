<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Renderable\Link;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $title = null;

    /** @var Collection<int, Author> */
    #[ORM\ManyToMany(targetEntity: Author::class, inversedBy: 'books')]
    public Collection $authors;

    /** @var Collection<int, Author> */
    #[ORM\ManyToMany(targetEntity: Author::class, inversedBy: 'translations')]
    #[ORM\JoinTable(name: 'book_translator')]
    public Collection $translators;

    #[ORM\Column(nullable: true)]
    public ?int $published = null;

    #[ORM\Column(nullable: true)]
    public ?int $first_published = null;

    #[ORM\ManyToOne(inversedBy: 'books')]
    public ?Publisher $publisher = null;

    #[ORM\Column(length: 13, nullable: true)]
    public ?string $isbn = null;

    #[ORM\ManyToOne(targetEntity: Series::class, inversedBy: 'volumes')]
    public ?Series $series = null;

    #[ORM\Column(nullable: true)]
    public ?int $series_volume = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    public ?\DateTime $created_at = null;

    /** @var Collection<int, Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'books')]
    public Collection $tags;

    public function __construct() {
        $this->authors = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->title ?? 'Untitled book';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getLinkOpenLibrary(): ?Link {
        if (empty($this->isbn)) return null;
        return Link::extern('OpenLibrary', 'https://openlibrary.org/isbn/' . $this->isbn)->setButton();
    }

    public function addTag(Tag $tag): void {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
    }

    public function getTags(): array {
        return $this->tags->map(fn ($tag) => $tag->getTag())->toArray();
    }
}
