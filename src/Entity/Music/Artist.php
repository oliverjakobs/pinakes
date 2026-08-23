<?php

namespace App\Entity;

use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
class Artist extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $name = null;

    /** @var Collection<int, Record> */
    #[ORM\OneToMany(targetEntity: Record::class, mappedBy: 'artist')]
    public Collection $records;

    public function __construct() {
        $this->records = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->name ?? 'Unknown artist';
    }

    public function getId(): ?int {
        return $this->id;
    }
}
