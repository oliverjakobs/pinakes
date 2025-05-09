<?php

namespace App\Entity;

use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Pinakes\Link;

#[ORM\Entity(repositoryClass: AuthorRepository::class)]
class Author extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Book>
     */
    #[ORM\ManyToMany(targetEntity: Book::class, inversedBy: 'authors')]
    private Collection $books;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $openlibrary = null;

    public function __construct() {
        $this->books = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->name ?? 'Unknown author';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getLinkOpenLibrary(): ?Link {
        if (null === $this->openlibrary) return null;
        return new Link($this->openlibrary, 'https://openlibrary.org/authors/' . $this->openlibrary, true);
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(string $name): static {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Collection<int, Book>
     */
    public function getBooks(): Collection {
        return $this->books;
    }

    public function addBook(Book $book): static {
        if (!$this->books->contains($book)) {
            $this->books->add($book);
        }

        return $this;
    }

    public function removeBook(Book $book): static {
        if ($this->books->removeElement($book)) {
            $book->removeAuthor($this);
        }
        return $this;
    }

    public function getOpenlibrary(): ?string
    {
        return $this->openlibrary;
    }

    public function setOpenlibrary(?string $openlibrary): static
    {
        $this->openlibrary = $openlibrary;

        return $this;
    }
}
