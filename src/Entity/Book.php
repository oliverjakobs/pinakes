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
    private ?string $title = null;

    /**
     * @var Collection<int, Author>
     */
    #[ORM\ManyToMany(targetEntity: Author::class, inversedBy: 'books')]
    private Collection $authors;

    #[ORM\Column(nullable: true)]
    private ?int $published = null;

    #[ORM\Column(nullable: true)]
    private ?int $first_published = null;

    #[ORM\ManyToOne(inversedBy: 'books')]
    private ?Publisher $publisher = null;

    #[ORM\Column(length: 13, nullable: true)]
    private ?string $isbn = null;

    public function __construct() {
        $this->authors = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->title ?? 'Untitled book';
    }

    public function getId(): ?int {
        return $this->id;
    }
    
    public function getLinksAuthors(): string {
        $links = array_map(fn ($a) => $a->getLinkSelf(), $this->authors->toArray());
        return implode('; ', $links);
    }

    public function getLinkOpenLibrary(): ?Link {
        if (null === $this->isbn) return null;
        return new Link('', 'https://openlibrary.org/isbn/' . $this->isbn, true);
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(string $title): static {
        $this->title = $title;
        return $this;
    }

    /**
     * @return Collection<int, Author>
     */
    public function getAuthors(): Collection {
        return $this->authors;
    }

    public function clearAuthors(): void {
        foreach ($this->authors as $author) {
            $this->removeAuthor($author);
        }
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

    public function getPublished(): ?int {
        return $this->published;
    }

    public function setPublished(?int $published): static {
        $this->published = $published;
        return $this;
    }

    public function getFirstPublished(): ?int {
        return $this->first_published;
    }

    public function setFirstPublished(?int $first_published): static {
        $this->first_published = $first_published;
        return $this;
    }

    public function getPublisher(): ?Publisher {
        return $this->publisher;
    }

    public function setPublisher(?Publisher $publisher): static {
        $this->publisher = $publisher;
        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $this->isbn = $isbn;

        return $this;
    }
}
