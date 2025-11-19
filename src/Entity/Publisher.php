<?php

namespace App\Entity;

use App\Repository\PublisherRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Pinakes\ViewElement;

#[ORM\Entity(repositoryClass: PublisherRepository::class)]
class Publisher extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $name = null;

    /**
     * @var Collection<int, Book>
     */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'publisher')]
    public Collection $books;

    public function __construct() {
        $this->books = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->name ?? 'Unknown publisher';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getLinkSelf(?string $value = null): ViewElement {
        return ViewElement::anchor($value ?? (string)$this, '/book/publisher/' . $this->getId());
    }

    public function getLinkShow(): ViewElement {
        return parent::getLinkSelf('Show');
    }
}
