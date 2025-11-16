<?php

namespace App\Entity;

use App\Repository\SeriesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Pinakes\ViewElement;

#[ORM\Entity(repositoryClass: SeriesRepository::class)]
class Series extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $name = null;

    /**
     * @var Collection<int, Book>
     */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'series')]
    public Collection $volumes;

    public function __construct() {
        $this->volumes = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->name;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getLinkSelf(?string $value = null): ViewElement {
        return ViewElement::anchor($value ?? (string)$this, '/book/series/' . $this->getId());
    }

    public function getLinkShow(): ViewElement {
        return parent::getLinkSelf('Show');
    }
}
