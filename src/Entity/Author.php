<?php

namespace App\Entity;

use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Pinakes\Link;

#[ORM\Entity(repositoryClass: AuthorRepository::class)]
class Author extends PinakesEntity {

    const ROLE_AUTHOR = 'author';
    const ROLE_TRANSLATOR = 'translator';
    const ROLE_EDITOR = 'editor';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $name = null;

    /**
     * @var Collection<int, Book>
     */
    #[ORM\ManyToMany(targetEntity: Book::class, mappedBy: 'authors')]
    public Collection $books;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $openlibrary = null;

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
}
