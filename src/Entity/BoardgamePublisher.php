<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\BoardgamePublisherRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoardgamePublisherRepository::class)]
class BoardgamePublisher extends PinakesEntity {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $name = null;

    /** @var Collection<int, Boardgame> */
    #[ORM\OneToMany(targetEntity: Boardgame::class, mappedBy: 'publisher')]
    public Collection $boardgames;

    public function __construct() {
        $this->boardgames = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->name ?? 'Unknown publisher';
    }

    public function getId(): ?int {
        return $this->id;
    }
}
