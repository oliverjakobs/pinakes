<?php declare(strict_types=1);

namespace App\Entity;

use App\Pinakes\Helper;
use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Database\Mapping;
use App\Renderable\Link;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Mapping\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Mapping\Column]
    public ?string $title = null;

    /** @var Collection<int, Author> */
    #[ORM\ManyToMany(targetEntity: Author::class, inversedBy: 'books', cascade: ['persist'])]
    #[Mapping\ManyToMany(target: Author::class, inversed_by: 'books')]
    public Collection $authors;

    /** @var Collection<int, Author> */
    #[ORM\ManyToMany(targetEntity: Author::class, inversedBy: 'translations', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'book_translator')]
    #[Mapping\ManyToMany(target: Author::class, inversed_by: 'translations', join_table: 'book_translator')]
    public Collection $translators;

    #[ORM\Column(nullable: true)]
    #[Mapping\Column]
    public ?int $published = null;

    #[ORM\Column(nullable: true)]
    #[Mapping\Column]
    public ?int $first_published = null;

    #[ORM\ManyToOne(targetEntity: Publisher::class, inversedBy: 'books', cascade: ['persist'])]
    #[Mapping\ManyToOne(target: Publisher::class, inversed_by: 'books')]
    public ?Publisher $publisher = null;

    #[ORM\Column(length: 13, nullable: true)]
    #[Mapping\Column]
    public ?string $isbn = null;

    #[ORM\ManyToOne(targetEntity: Series::class, inversedBy: 'volumes', cascade: ['persist'])]
    #[Mapping\ManyToOne(target: Series::class, inversed_by: 'volumes')]
    public ?Series $series = null;

    #[ORM\Column(nullable: true)]
    #[Mapping\Column]
    public ?int $series_volume = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Mapping\Column]
    public \DateTime $created_at;

    /** @var Collection<int, Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'books', cascade: ['persist'])]
    #[Mapping\ManyToMany(target: Tag::class, inversed_by: 'books')]
    public Collection $tags;

    public function __construct() {
        $this->created_at = new \DateTime();
        $this->authors = new ArrayCollection();
        $this->translators = new ArrayCollection();
        $this->tags = new ArrayCollection();
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

    public function addTag(Tag $tag): void {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
    }
}
