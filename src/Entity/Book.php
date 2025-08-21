<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Pinakes\Link;

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

    #[ORM\OneToOne(mappedBy: 'book')]
    public ?SeriesVolume $volume = null;

    /**
     * @var Collection<int, Genre>
     */
    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'books')]
    public Collection $genre;

    public function __construct() {
        $this->authors = new ArrayCollection();
        $this->series_volumes = new ArrayCollection();
        $this->genre = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->title ?? 'Untitled book';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getLinkOpenLibrary(): ?Link {
        if (empty($this->isbn)) return null;
        return new Link('', 'https://openlibrary.org/isbn/' . $this->isbn, true);
    }

    public function getSeries(): ?Series {
        return $this->volume?->series;
    }

    public function getSeriesVolume(): ?string {
        return $this->volume?->volume;
    }

    public function getGenreTags(): array {
        return $this->genre->map(fn ($genre) => $genre->getTag())->toArray();
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

    public function clearGenre(): void {
        foreach ($this->genre as $genre) {
            $this->removeGenre($genre);
        }
    }

    public function addGenre(Genre $genre): static {
        if (!$this->genre->contains($genre)) {
            $this->genre->add($genre);
            $genre->addBook($this);
        }

        return $this;
    }

    public function removeGenre(Genre $genre): static {
        if ($this->genre->removeElement($genre)) {
            $genre->removeBook($this);
        }

        return $this;
    }
}
