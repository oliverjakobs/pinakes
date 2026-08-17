<?php declare(strict_types=1);

namespace App\Entity;

use App\Pinakes\Pinakes;
use App\Repository\SeriesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Database\Mapping;

#[ORM\Entity(repositoryClass: SeriesRepository::class)]
class Series extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Mapping\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Mapping\Column]
    public ?string $name = null;

    /** @var Collection<int, Book> */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'series')]
    #[Mapping\OneToMany(target: Book::class, mappedBy: 'series')]
    public Collection $volumes;

    #[Mapping\Count(property: 'volumes')]
    public ?int $volume_count = null;

    public function __construct() {
        $this->volumes = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->name ?? 'Untitled Series';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getAuthors(): Collection {
        return Author::getRepository()->findBySeries($this);
    }
}
