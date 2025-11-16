<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Pinakes\ViewElement;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $title = null;

    /**
     * @var Collection<int, Author>
     */
    #[ORM\ManyToMany(targetEntity: Author::class, inversedBy: 'books')]
    public Collection $authors;

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

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'books')]
    public Collection $tags;

    public function __construct() {
        $this->authors = new ArrayCollection();
        $this->series_volumes = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->title ?? 'Untitled book';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getLinkOpenLibrary(): ?ViewElement {
        if (empty($this->isbn)) return null;
        return ViewElement::anchor('', 'https://openlibrary.org/isbn/' . $this->isbn, true);
    }

    public function getSeries(): ?Series {
        return $this->volume?->series;
    }

    public function getSeriesVolume(): ?string {
        return $this->volume?->volume;
    }

    public function getTags(): array {
        return $this->tags->map(fn ($tag) => $tag->getTag())->toArray();
    }

    public function clearAuthors(): void {
        foreach ($this->authors as $author) {
            $this->removeAuthor($author);
        }
    }
    
    public function setAuthors(array $authors): static {
        return $this;
    }

    public function addAuthor(Author $author): static {
        if (!$this->authors->contains($author)) {
            $this->authors->add($author);
            $author->addBook($this);
        }

        return $this;
    }

    public function removeAuthor(Author $author): static {
        if ($this->authors->removeElement($author)) {
            $author->removeBook($this);
        }

        return $this;
    }
}
