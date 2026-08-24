<?php declare(strict_types=1);

namespace App\Entity;

use App\Database\Mapping;

class BoardgamePublisher extends PinakesEntity {

    #[Mapping\Column]
    private ?int $id = null;

    #[Mapping\Column]
    public ?string $name = null;

    /** @var Boardgame[] */
    #[Mapping\OneToMany(target: Boardgame::class, mapped_by: 'publisher')]
    public array $boardgames;

    #[Mapping\Count(property: 'boardgames')]
    public ?int $boardgame_count = null;

    public function __toString(): string {
        return $this->name ?? 'Unknown publisher';
    }

    public function getId(): ?int {
        return $this->id;
    }
}
