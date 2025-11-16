<?php

namespace App\Entity;

use App\Repository\TagRepository;
use App\Pinakes\ViewElement;
use App\Pinakes\Renderer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $name = null;


    #[ORM\Column(length: 9, options: ['default' => '#ffffff'])]
    public ?string $color = null;

    /**
     * @var Collection<int, Book>
     */
    #[ORM\ManyToMany(targetEntity: Book::class, mappedBy: 'tags')]
    public Collection $books;

    public function __construct() {
        $this->books = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->name ?? 'Unknown tag';
    }

    public function getId(): ?int {
        return $this->id;
    }

    private function getUrl(): string {
        return '/book/tag/' . $this->getId();
    }

    public function getLinkSelf(?string $value = null): ViewElement {
        return ViewElement::anchor($value ?? (string)$this, $this->getUrl());
    }

    public function getLinkShow(): ViewElement {
        return parent::getLinkSelf('Show');
    }

    public function getTag(): ViewElement {
        return ViewElement::tag((string) $this, $this->color, $this->getUrl());
    }
}
