<?php

namespace App\Entity;

use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthorRepository::class)]
class Author extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Paper::class, mappedBy: 'authors', cascade: ['remove'])]
    private Collection $papers;

    #[ORM\ManyToMany(targetEntity: Book::class, mappedBy: 'authors', cascade: ['remove'])]
    private Collection $books;

    public function __construct() {
        $this->papers = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->name;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Paper>
     */
    public function getPapers(): Collection
    {
        return $this->papers;
    }

    public function addPaper(Paper $paper): static
    {
        if (!$this->papers->contains($paper)) {
            $this->papers->add($paper);
            $paper->addAuthor($this);
        }

        return $this;
    }

    public function removePaper(Paper $paper): static
    {
        if ($this->papers->removeElement($paper)) {
            $paper->removeAuthor($this);
        }

        return $this;
    }

        /**
     * @return Collection<int, Book>
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function addBook(Book $book): static
    {
        if (!$this->books->contains($book)) {
            $this->books->add($book);
            $book->addAuthor($this);
        }

        return $this;
    }

    public function removeBook(Book $book): static
    {
        if ($this->books->removeElement($book)) {
            $book->removeAuthor($this);
        }

        return $this;
    }
}
