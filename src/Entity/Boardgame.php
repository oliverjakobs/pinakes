<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\BoardgameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoardgameRepository::class)]
class Boardgame extends PinakesEntity {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $name = null;

    /** @var Collection<int, Boardgame> */
    #[ORM\OneToMany(targetEntity: Boardgame::class, mappedBy: 'base_game')]
    public Collection $extensions;

    #[ORM\ManyToOne(inversedBy: 'extensions')]
    public ?Boardgame $base_game = null;

    #[ORM\ManyToOne(inversedBy: 'boardgames')]
    public ?BoardgamePublisher $publisher = null;

    #[ORM\Column()]
    public ?int $min_player = null;

    #[ORM\Column(nullable: true)]
    public ?int $max_player = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    public ?\DateTime $created_at = null;

    public function __construct() {
        $this->extensions = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->name ?? 'Untiteld boardgame';
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getPlayerCount(): string {
        $result = (string) $this->min_player;
        if (0 !== (int) $this->max_player) {
            $result .= '-' . $this->max_player;
        }
        return $result;
    }
}
