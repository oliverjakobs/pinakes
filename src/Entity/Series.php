<?php

namespace App\Entity;

use App\Repository\SeriesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeriesRepository::class)]
class Series extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $name = null;

    /**
     * @var Collection<int, SeriesVolume>
     */
    #[ORM\OneToMany(targetEntity: SeriesVolume::class, mappedBy: 'series', cascade:['persist', 'remove'])]
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

    public function addVolume(SeriesVolume $volume): static {
        // TODO prevent duplicates
        $this->volumes->add($volume);
        $volume->series = $this;
        return $this;
    }
}
