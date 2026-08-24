<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class Boardgame extends PinakesEntity {

    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public ?string $name = null;

    /** @var Boardgame[] */
    #[Mapping\OneToMany(target: Boardgame::class, mapped_by: 'base_game')]
    public array $extensions = [];

    #[Mapping\ManyToOne(target: Boardgame::class, inversed_by: 'extensions')]
    public ?Boardgame $base_game = null;

    #[Mapping\ManyToOne(target: BoardgamePublisher::class, inversed_by: 'boardgames')]
    public ?BoardgamePublisher $publisher = null;

    #[Mapping\Column]
    public int $min_player = 1;

    #[Mapping\Column]
    public ?int $max_player = null;

    #[Mapping\Column]
    public ?\DateTime $created_at = null;

    public function __construct() {
        $this->created_at = new \DateTime();
    }

    public function __toString(): string {
        return $this->name ?? 'Untitled boardgame';
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
